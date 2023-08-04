## Perspective Novaposhta Shipping

To install this package use the following steps:

1. Go to Magento 2 root directory.
2. For Magento 2 use following command enter the following commands
3. For Magento 2  (from 2.4.5+) use following commands:

3.1. Installation in app/code/  

3.1.1. Install BoxPacker library  
        ```
        composer require dvdoug/boxpacker
        ```  

3.1.2. Install Perspective Novaposhta Catalog Repository  
        ``` 
        composer config repositories.perspective_novaposhtacatalog vcs https://github.com/PerspectiveTeam/NovaposhtaCatalog.git
        ```  

3.1.3. Install Perspective Novaposhta Catalog Module  
        ```
        composer require perspectiveteam/module-novaposhtacatalog:"*"  
        ```  

3.1.4. Install Perspective Novaposhta Shipping Repository  
        ```
        composer config repositories.perspective_novaposhtashipping vcs https://github.com/PerspectiveTeam/NovaposhtaShipping.git
        ```  

3.1.5. Install Perspective Novaposhta Shipping Module  
        ```
        composer require perspectiveteam/module-novaposhtashipping:"*"  
        ```  

3.2. OR Installation via composer  
        ```
        composer require perspectiveteam/module-novaposhtashipping:"*"  
        ```  

4. Wait while dependencies are updated.
5. Make an ordinary setup for the module
