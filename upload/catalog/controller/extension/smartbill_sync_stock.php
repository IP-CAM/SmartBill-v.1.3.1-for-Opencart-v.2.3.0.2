<?php

class ControllerExtensionSmartbillSyncStock extends Controller {

    public function index() {
        $this->load->model('setting/setting');
        $options = $this->model_setting_setting->getSetting('SMARTBILL');
        $file=DIR_LOGS.'smartbill_sincronizare_stocuri.log';
        $headers = getallheaders();
        $json = file_get_contents('php://input');
        $products=json_decode($json);
        try{
            if ($this->request->server['REQUEST_METHOD'] == 'POST'  && isset($headers['Authorization'])){
                if(!isset($options['SMARTBILL_SYNC_STOCK']) || $options['SMARTBILL_SYNC_STOCK']!='1'){
                    throw new \Exception('Setarea "Actualizeaza stocurile din magazinul online" nu este activa.');
                }
                
                $token=$options['SMARTBILL_API_TOKEN'];
                if(!empty($token) && !empty($headers['Authorization']) && $headers['Authorization'] == 'Bearer '.$token){
                    
                    if(is_null($products)){
                        throw new \Exception('Eroare sintaxa. Verifica valididatea JSON-ulului trimis.'); 
                    }
                    $selected_stock=false;
                    if (isset($options['SMARTBILL_USED_STOCK'])) {
                        $selected_stock = $options['SMARTBILL_USED_STOCK'];
                        if (empty($selected_stock) || $selected_stock == 'fara-gestiune'){
                            $selected_stock = false;
                            throw new \Exception('Eroare actualizare stoc. Gestiunea monitorizata nu a fost setata in modulul SmartBill.');
                        }
                    } else {
                        $selected_stock = false;
                        throw new \Exception('Eroare actualizare stoc. Gestiunea monitorizata nu a fost setata in modulul SmartBill.');
                    }

                    $this->smartbill_opencart_stocks_update($products,$options['SMARTBILL_PRODUCT_SKU_TYPE'],$options['SMARTBILL_USED_STOCK']);

                }else{
                    if(file_exists($file)){
                        error_log("======================================================================================================================================".PHP_EOL,3, $file);
                    }
                    $this->smartbill_log($products,"Authentication failed when PRODUCTS RECEIVED");
                    throw new \Exception('Autentificare esuata. Asigura-te ca tokenul folosit pentru trimiterea notificarii de stoc este corect si ca serverul tau permite autentificarea prin headers.');
                }
            }else{
                throw new \Exception('Invalid Request');
            }
        }catch(Exception $e){
            http_response_code(401);
            echo $e->getMessage();
        }
    }

    private function smartbill_opencart_stocks_update($products,$sku_type,$selected_stock){
        $file=DIR_LOGS.'smartbill_sincronizare_stocuri.log';
        if (isset($products->products)){
            $products = $products->products;
        }
        if(file_exists($file)){
            error_log("======================================================================================================================================".PHP_EOL,3, $file);
        }

        if(is_array($products)){
            $this->smartbill_log($products,"PRODUCTS RECEIVED");

            if(count($products)==0){
                echo('Testul de sincronizare stoc a fost facut cu succes!'.PHP_EOL); 
            }

            $this->smartbill_log("START STOCK SYNC","INFO");
            foreach($products as $product){
                if ($selected_stock && strtolower($product->warehouse) == strtolower($selected_stock)){
                    $product_id=null;
                    $has_options=false;
                    if(isset($product->productCode) && trim($product->productCode) != ""){
                        $product_id=$this->smartbill_opencart_get_product_by_sku($product->productCode,$sku_type);
                        if($product_id==false){
                            echo('"Eroare actualizare stoc. Produsul cu codul '.$product->productCode.' nu a fost gasit in nomenclatorul Opencart."'.PHP_EOL);  
                            $this->smartbill_log("Product with product code $product->productCode not found!","ERROR");  

                            if(preg_match("/(?=.*[;])(?=.*[(])(?=.*[)])/i", $product->productName)===0){
                                $product_id=$this->smartbill_opencart_get_product_by_name($product->productName); 

                                if($product_id==false){
                                    echo('"Eroare actualizare stoc. Produsul cu numele '.$product->productName.' nu a fost gasit in nomenclatorul Opencart."'.PHP_EOL);
                                    $this->smartbill_log("Product with product name $product->productName not found!","ERROR");   
                                }
                            }else{
                                $temp_name=trim($product->productName);
                                $temp_name=substr($temp_name, 0, -1);
                                $temp_name=explode('(',$temp_name);
                                $product_options=trim($temp_name[1]);
                                $product_options=explode(';',$product_options);
                                $temp_name=trim($temp_name[0]);
                                $product_id=$this->smartbill_opencart_get_product_with_options_by_name($temp_name,$product_options);
                                $has_options=true;

                                if($product_id==false){
                                    echo('"Eroare actualizare stoc. Produsul cu variatii cu numele '.$product->productName.' nu a fost gasit in nomenclatorul Opencart."'.PHP_EOL);
                                    $this->smartbill_log("Product with product name $product->productName not found!","ERROR");   
                                }
                            }
                        }
                    }else {
                        //A product has options if in the product name you can find these caractres "(", ")", ";"
                        //Users must be informed to NOT use all of these caracters in the name of any product
                        if(preg_match("/(?=.*[;])(?=.*[(])(?=.*[)])/i", $product->productName)===0){
                           $product_id=$this->smartbill_opencart_get_product_by_name($product->productName); 

                            if($product_id==false){
                                echo('"Eroare actualizare stoc. Produsul cu numele '.$product->productName.' nu a fost gasit in nomenclatorul Opencart."'.PHP_EOL);
                                $this->smartbill_log("Product with product name $product->productName not found!","ERROR");   
                            }
                        }else{
                            $temp_name=trim($product->productName);
                            $temp_name=substr($temp_name, 0, -1);
                            $temp_name=explode('(',$temp_name);
                            $product_options=trim($temp_name[1]);
                            $product_options=explode(';',$product_options);
                            $temp_name=trim($temp_name[0]);
                            $product_id=$this->smartbill_opencart_get_product_with_options_by_name($temp_name,$product_options);
                            $has_options=true;

                            if($product_id==false){
                                echo('"Eroare actualizare stoc. Produsul cu variatii cu numele '.$product->productName.' nu a fost gasit in nomenclatorul Opencart."'.PHP_EOL);
                                $this->smartbill_log("Product with product name $product->productName not found!","ERROR");   
                            }
                        }
                    } 
                   
                    if(!is_null($product_id) && $product_id!=false){ 
                        if(!isset($product->productCode) || empty($product->productCode)){
                            if($has_options){
                                $this->smartbill_log("Product $product->productName has been found by name. Attempting to update stocks.","INFO");
                            }else{
                                $this->smartbill_log("Product $product->productName with variations has been found by name. Attempting to update stocks.","INFO");
                            }
                        }else{
                            $this->smartbill_log("Product $product->productName has been found by sku: $product->productCode. Attempting to update stocks.","INFO");
                        }

                        $product->quantity = filter_var($product->quantity, FILTER_SANITIZE_NUMBER_INT);
                        
                        //Update stocks for product by product id
                        $update_stocks=$this->db->query("UPDATE ".DB_PREFIX ."product SET `quantity` = '$product->quantity' WHERE `product_id` = '$product_id'");
                        if($update_stocks==false){
                            echo "Eroare actualizare stoc. Stocul produsului $product->productName nu a fost putut fi actualizat.";
                            if(!isset($product->productCode) || empty($product->productCode)){
                                $this->smartbill_log("Couldn't update stocks for product ".$product->productName."!" ,"ERROR");
                            }else{
                                $this->smartbill_log("Couldn't update stocks for product ".$product->productName." with sku: ".$product->productCode."!" ,"ERROR"); 
                            }
                        }else{
                            $this->smartbill_log("Quantity of product ".$product->productName." with id ".$product_id." has been updated to ". $product->quantity.".","INFO");             
                            if(!isset($product->productCode) || empty($product->productCode)){
                                echo('"Stoc actualizat pentru produsul cu id-ul '.$product_id.' si numele '.$product->productName.'. Stoc nou: '. $product->quantity.'"'.PHP_EOL);
                            }else{
                                echo('"Stoc actualizat pentru produsul cu id-ul '.$product_id.' si codul '.$product->productCode.'. Stoc nou: '. $product->quantity.'"'.PHP_EOL);
                            }
                        }

                        if($has_options){
                            //Update stocks for all options of the product by product id
                            $update_stocks=$this->db->query("UPDATE ".DB_PREFIX ."product INNER JOIN ".DB_PREFIX ."product_option_value 
                            ON ".DB_PREFIX ."product.product_id = ".DB_PREFIX ."product_option_value.product_id 
                            SET ".DB_PREFIX ."product_option_value.quantity = $product->quantity
                            WHERE (((".DB_PREFIX ."product.product_id)=$product_id));");
                            
                            if($update_stocks==false){
                                echo "Eroare actualizare stoc variatiuni. Stocul produsului $product->productName nu a fost putut fi actualizat.";
                                if(!isset($product->productCode) || empty($product->productCode)){
                                    $this->smartbill_log("Couldn't update stocks for product ".$product->productName."!" ,"ERROR");
                                }else{
                                    $this->smartbill_log("Couldn't update stocks for product ".$product->productName." with sku: ".$product->productCode."!" ,"ERROR"); 
                                }
                            }else{
                                $this->smartbill_log("Quantity of product ".$product->productName." with id ".$product_id." has been updated to ". $product->quantity.".","INFO");             
                                if(!isset($product->productCode) || empty($product->productCode)){
                                    echo('"Stoc variatiuni actualizat pentru produsul cu id-ul '.$product_id.' si numele '.$product->productName.'. Stoc nou: '. $product->quantity.'"'.PHP_EOL);
                                }else{
                                    echo('"Stoc variatiuni actualizat pentru produsul cu id-ul '.$product_id.' si codul '.$product->productCode.'. Stoc nou: '. $product->quantity.'"'.PHP_EOL);
                                }
                            }
                        }
                    }
                }else{
                    $this->smartbill_log( "Plugin configured warehouse: ".$selected_stock." doesn't match received product warehouse: ".$product->warehouse,"ERROR");
                    echo('"Eroare actualizare stoc. Gestiune configurata in modul: '.$selected_stock.'. Gestiunea produsului '.$product->productName.': '.$product->warehouse.'"'.PHP_EOL); 
                }
            }
            $this->smartbill_log("STOP STOCK SYNC","INFO");
        }else{
            $this->smartbill_log($products,"INVALID PRODUCTS");
        }
    }

    private function smartbill_opencart_get_product_by_name($product_name){
        $product_name=$this->db->escape($product_name);
        $product_name = filter_var($product_name, FILTER_SANITIZE_STRING);
        $query = $this->db->query("SELECT `product_id` FROM ".DB_PREFIX."product_description WHERE name LIKE '".$product_name."'");

        if ($query->num_rows) {
            return  $query->row['product_id'];
        }else{
            return false;
        }
    }

    private function smartbill_opencart_get_product_by_sku($product_code,$sku_type){
        $product_code=$this->db->escape($product_code);
        $product_code = filter_var($product_code, FILTER_SANITIZE_STRING);
        $query = $this->db->query( "SELECT `product_id` FROM ".DB_PREFIX."product WHERE `".$sku_type."` = '".$product_code."' ");
        if ($query->num_rows) {
            return  $query->row['product_id'];
        }else{
            return false;
        }
    }

    private function smartbill_opencart_get_product_with_options_by_name($product_name,$options){
        $product_name=$this->db->escape($product_name);
        $product_name = filter_var($product_name, FILTER_SANITIZE_STRING);

        //Create a sting with options (from smartbill product name) and sanitize
        $product_options="";
        foreach($options as $option){
            $option=$this->db->escape($option);
            $option=filter_var($option, FILTER_SANITIZE_STRING);
            $product_options.="'".trim($option)."'";
            $product_options.=",";
        }
        $product_options=substr($product_options, 0, -1);

        //Select products that have only the searched options
        //Has a count on options for excluding products that have more values in options
        //That means that for example if i search for Hoodie with pocket (Dimension: 40x60cm; Color: Red) there must be a product with the options dimension and color that have ONLY 1 values each, Red and 40x60cm
       $query = $this->db->query(" SELECT product_id, Count(Options) AS CountOfOptions 
        FROM (
            SELECT ".DB_PREFIX."product_description.product_id, ((".DB_PREFIX."option_description.name & ': ' & ".DB_PREFIX."option_value_description.name)) AS Options, ".DB_PREFIX."product_description.name 
            FROM ".DB_PREFIX."product_description 
            INNER JOIN (".DB_PREFIX."option_description 
            INNER JOIN (".DB_PREFIX."product_option_value 
            INNER JOIN ".DB_PREFIX."option_value_description 
            ON ".DB_PREFIX."product_option_value.option_value_id = ".DB_PREFIX."option_value_description.option_value_id)
            ON (".DB_PREFIX."option_description.option_id = ".DB_PREFIX."product_option_value.option_id) 
            AND (".DB_PREFIX."option_description.option_id = ".DB_PREFIX."option_value_description.option_id)) 
            ON ".DB_PREFIX."product_description.product_id = ".DB_PREFIX."product_option_value.product_id 
            WHERE (((((".DB_PREFIX."option_description.name & ': ' & ".DB_PREFIX."option_value_description.name))) 
            In ($product_options)) 
            And ((".DB_PREFIX."product_description.name)='$product_name'))) AS CountOfOptions 
            GROUP BY product_id HAVING (((Count(Options))=".count($options)."))");
        if ($query->num_rows) {
            return  $query->row['product_id'];
        }else{
            return false;
        }
        
    }

    function smartbill_log($message,$type){
        $file=DIR_LOGS.'smartbill_sincronizare_stocuri.log';
        if(file_exists($file)){
            $format_message=date('Y-m-d H:i:sO').";".$type.";".json_encode($message).PHP_EOL;
            error_log($format_message,3, $file);
        }
    }

}
