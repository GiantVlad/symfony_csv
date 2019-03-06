<h3>Import CSV price-list to the DB</h3>

<span>#php7 #Symfony4 #MySql</span>

You can run it on Vagrant BOX or in another environment

For Vagrant:
- Run "vagrant up" from project folder on the host machine.
  Content from "csv_proj" folder will be mapped to the "/var/www/html" folder on Vagrant VM.
- Start ssh session on Vagrant VM. User and pass are "vagrant", or through ssh key.
- Change permissions (sudo chmod -R <folder_name> ), if it necessary.
- On VM. Type "cd /var/www/html"
- Run "composer install"
- If you want use a new DB, you can connect to MySql. Run: "mysql -u root -p secret", and create a new DB: "CREATE DATABASE IF NOT EXISTS wrenTest;".
- Edit .env file to connect to your DB, if it necessary.
- Run "php bin/console doctrine:migrations:migrate".
  If table "tblProductData" does not exists, it will be created. And then the "strProductPrice" and "strProductStock" columns will be added.
- Move your CSV file to "upload" folder, The file should be named 'stock.csv' and should contain this first header row: "Product Code,Product Name,Product Description,Stock,Cost in GBP,Discontinued"
- Run "php bin/console app:import-csv" or "php bin/console app:import-csv test".
