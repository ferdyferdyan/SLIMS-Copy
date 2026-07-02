# SLiMS Copy Cataloging Plugin
This plugin is used to copy catalogs between institutions using the SLiMS platform. When a user clicks on a title, SLiMS will automatically search the OPAC/catalog for the book in their collection. 

Developed by: **Ruang Perpustakaan** ([ruangperpustakaan.com](https://ruangperpustakaan.com))

---

## 🛠️ Installation Steps (Installation)

1. **Download and Extract:**
   Download this plugin file, then extract the ZIP file into the `plugins/` directory located inside your SLiMS root folder.
   
2. **Make Sure Directory Structure is Correct:**
   Make sure all code and plugin files are in the properly named directory:
   
   ```text
   slims_root/plugins/SLIMS_COPY/
   ```

⚙️ Catalog List Configuration (Server List)  Before or after activation, you can customize the list of target catalog server addresses in the plugin's configuration file:

 File Location: SLIMS_COPY/server_list.inc.php 

Instructions: Open the file using a text editor, then add a line of code listing the SLiMS-based libraries you want to target to the provided array variable.

🔌 Activation Steps (Activation)
1.  Log in to your SLiMS Admin panel. 
2.  Open the System module in the main navigation menu. 
3.  Select the Plugins submenu. 
4.  Search for the SLiMS Copy plugin. 
5. Change the plugin status from Inactive to Active.

📖 How to Use (Usage)
1.  After the plugin is successfully activated, open the Bibliography module. 
2.  In the module's submenu, you will find a new menu item named **SLiMS Copy**. 
3.  Enter a book keyword in the search field. 
4.  The plugin will begin searching for books based on the SLIMS catalog list entered in the server's list.php file. 
5.  Select the appropriate book and click **Save Selected Data**. The book will automatically be saved in your Bibliography. 
6.  Use this menu to automatically search and copy catalog data based on book titles.

📄 License & Contribution
This plugin was developed to make it easier for librarians to efficiently process bibliographic data. It is fully supported by the Ruang Perpustakaan ecosystem.

*Bibliographic data copied from other catalogs is not necessarily correct. It will vary from library to library and will be in accordance with that library's bibliographic policy. Please check the data again after download before use*


