## How SmartBill setting keys are used
In twig templates the setting keys are lowercase, and in php code they are uppercase (i.e. SMARTBILL_USER from code will be used as smartbill_user in twig files)

## Query to set module status to enabled 
INSERT INTO ##prefix##setting ( `store_id`, `code`, `key`,`value`, `serialized`) VALUES ( 0, 'SMARTBILL', 'smartbill_status', '1', 0);

## Query to set module status to disabled 

DELETE FROM ##prefix##setting WHERE `key`='smartbill_status'