<?php
/**
 * Plugin Name: Bulk SEO CSV Updater
 * Description: Updates meta titles and descriptions from CSV files based on URL path. Supports posts, pages, categories, and tags via Yoast SEO. Detects taxonomy from URL and supports OpenGraph & JSON-LD.
 * Version: 2.2
 * Author: WizzDev Tools
 */

// Register plugin menu in WordPress admin
add_action('admin_menu', 'bscu_add_admin_menu');

function bscu_add_admin_menu() {
    // Create admin page under main menu
    add_menu_page('Bulk SEO CSV Updater', 'Bulk SEO CSV', 'manage_options', 'bulk-seo-csv-updater', 'bscu_admin_page');
}

function bscu_admin_page() {
    ?>
    <div class="wrap">
        <h1>Bulk SEO CSV Updater</h1>
        <form method="post" enctype="multipart/form-data">
            <h2>Update Meta Titles</h2>
            <input type="file" name="title_csv" accept=".csv" required />
            <input type="submit" name="upload_title" class="button button-primary" value="Update Titles" />
        </form>
        <form method="post" enctype="multipart/form-data" style="margin-top:40px">
            <h2>Update Meta Descriptions</h2>
            <input type="file" name="description_csv" accept=".csv" required />
            <input type="submit" name="upload_description" class="button button-primary" value="Update Descriptions" />
        </form>
    </div>
    <?php

    if (isset($_POST['upload_title']) && !empty($_FILES['title_csv']['tmp_name'])) {
        bscu_process_csv($_FILES['title_csv']['tmp_name'], 'title');
    }

    if (isset($_POST['upload_description']) && !empty($_FILES['description_csv']['tmp_name'])) {
        bscu_process_csv($_FILES['description_csv']['tmp_name'], 'description');
    }
}

function update_yoast_taxonomy_meta($taxonomy, $term_id, $meta_key, $meta_value) {
    $option = 'wpseo_taxonomy_meta';
    $meta = get_option($option);
    if (!is_array($meta)) $meta = [];
    if (!isset($meta[$taxonomy])) $meta[$taxonomy] = [];
    if (!isset($meta[$taxonomy][$term_id])) $meta[$taxonomy][$term_id] = [];
    $meta[$taxonomy][$term_id][$meta_key] = $meta_value;
    update_option($option, $meta);
}

function bscu_process_csv($file, $type) {
    // Main function to process uploaded CSV file
        // Try opening uploaded file
    if (($handle = fopen($file, 'r')) !== FALSE) {
                $row = 0; // Counter to skip header row
        echo '<div class="notice notice-success"><ul>';
        $header = fgetcsv($handle, 1000, ",");
        if (count($header) < 2 || strtolower(trim($header[1])) !== $type) {
            echo '<div class="notice notice-error"><p>Invalid CSV: second column must be labeled "' . esc_html($type) . '".</p></div>';
            return;
        }
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row++;
            if ($row == 1) continue;
            $url = isset($data[0]) ? trim($data[0]) : '';
            $value = isset($data[1]) ? trim($data[1]) : '';

            if (!$url || !$value) continue;

                        // Extract path from full URL (ignores domain)
            $path = parse_url($url, PHP_URL_PATH);
            $segments = explode('/', trim($path, '/'));
            $slug = end($segments);
            $taxonomy = in_array('tag', $segments) ? 'post_tag' : 'category';
            $updated = false;

            global $wpdb;

            $post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_status = 'publish' AND post_type IN ('post','page') LIMIT 1",
                $slug
            ));

            if (!$post_id) {
                $post_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts WHERE guid LIKE %s LIMIT 1",
                    '%' . $wpdb->esc_like($path)
                ));
            }

            if ($post_id) {
                $meta_key = $type === 'title' ? '_yoast_wpseo_title' : '_yoast_wpseo_metadesc';
                update_post_meta($post_id, $meta_key, $value);
                echo "<li style='color:green'>✅ Updated ($type) for post/page: $path</li>";
                $updated = true;
            }

            if (!$updated) {
                $term = get_term_by('slug', $slug, $taxonomy);

                if ($term && !is_wp_error($term)) {
                    $meta_key = $type === 'title' ? 'wpseo_title' : 'wpseo_desc';
                    update_yoast_taxonomy_meta($taxonomy, $term->term_id, $meta_key, $value);
                    echo "<li style='color:green'>✅ Updated ($type) for $taxonomy: $path</li>";
                    $updated = true;
                }
            }

            if (!$updated) {
                echo "<li style='color:red'>❌ Not found: $path</li>";
            }
        }
        fclose($handle);
        echo '</ul></div>';
    } else {
        echo '<div class="notice notice-error"><p>Failed to open CSV file.</p></div>';
    }
}

// Wymuszenie poprawnych tytułów i opisów z Yoast SEO
add_filter('wpseo_title', 'bscu_force_yoast_title_for_taxonomy');
add_filter('wpseo_metadesc', 'bscu_force_yoast_description_for_taxonomy');
add_filter('wpseo_opengraph_title', 'bscu_force_yoast_og_title', 11);
add_filter('wpseo_opengraph_desc', 'bscu_force_yoast_og_description', 11);
add_filter('wpseo_json_ld_output', 'bscu_update_yoast_ld_json');

function bscu_force_yoast_title_for_taxonomy($title) {
    if (is_category() || is_tag()) {
        $term = get_queried_object();
        $meta = get_option('wpseo_taxonomy_meta');
        if (!empty($meta[$term->taxonomy][$term->term_id]['wpseo_title'])) {
            return $meta[$term->taxonomy][$term->term_id]['wpseo_title'];
        }
    }
    return $title;
}

function bscu_force_yoast_description_for_taxonomy($desc) {
    if (is_category() || is_tag()) {
        $term = get_queried_object();
        $meta = get_option('wpseo_taxonomy_meta');
        if (!empty($meta[$term->taxonomy][$term->term_id]['wpseo_desc'])) {
            return $meta[$term->taxonomy][$term->term_id]['wpseo_desc'];
        }
    }
    return $desc;
}

function bscu_force_yoast_og_title($og_title) {
    return bscu_force_yoast_title_for_taxonomy($og_title);
}

function bscu_force_yoast_og_description($og_desc) {
    return bscu_force_yoast_description_for_taxonomy($og_desc);
}

function bscu_update_yoast_ld_json($data) {
    if (is_category() || is_tag()) {
        $term = get_queried_object();
        $meta = get_option('wpseo_taxonomy_meta');
        if (!empty($meta[$term->taxonomy][$term->term_id]['wpseo_desc'])) {
            $desc = $meta[$term->taxonomy][$term->term_id]['wpseo_desc'];
            foreach ($data as &$node) {
                if (!empty($node['@type']) && in_array($node['@type'], ['WebPage', 'CollectionPage', 'Article'])) {
                    $node['description'] = $desc;
                }
            }
        }
    }
    return $data;
}
