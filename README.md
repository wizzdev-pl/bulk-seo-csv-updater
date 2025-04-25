# bulk-seo-csv-updater
🛠 Bulk SEO CSV Updater – User Manual
## 📥 Plugin Installation
Log in to your WordPress admin panel.<br/>
Go to Plugins → Add New → Upload Plugin.<br/>
Select the .zip file (e.g., bulk-seo-csv-updater-v2.5.zip) and click Install Now.<br/>
After installation, click Activate Plugin.<br/>

## 📁 Preparing CSV Files
✅ Meta Title
 The CSV file must have two columns:<br/>
  cpp<br/>
  CopyEdit
  
url,title<br/>
https://example.com/my-post,My Custom Title for SEO<br/>
https://example.com/page-about,About Us | Example<br/>

## ✅ Meta Description
 The CSV file must have two columns:<br/>
  perl<br/>
  CopyEdit<br/>
  
url,description<br/>
https://example.com/my-post,This page explains our services in detail.<br/>
https://example.com/page-about,Learn more about our team and mission.


## 📤 Updating SEO Data
In the WordPress menu, select Bulk SEO CSV.
Use the appropriate field:
Update Meta Titles – upload the file with columns url,title
 Click Update Titles


Update Meta Descriptions – upload the file with columns url,description
 Click Update Descriptions


A log will appear on the page indicating successes or errors.

## ℹ️ Plugin Supports:<br/>
  ✅ Posts and Pages<br/>
 ✅ Categories (category)<br/>
 ✅ Tags (post_tag)<br/>
 ✅ Both full URLs and just the path (e.g., /blog/my-post/)

## 🔒 Notes:
If the CSV has an incorrect header (e.g., Title instead of title), the import will be blocked.


Missing value = the row will be skipped.


Data for categories and tags is saved in wpseo_taxonomy_meta.





