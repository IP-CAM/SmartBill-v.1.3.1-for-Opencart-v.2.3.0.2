<?php

class ControllerExtensionSmartbillSyncStock extends Controller {

    public function index() {
        $this->load->model('setting/setting');
        $options = $this->model_setting_setting->getSetting('SMARTBILL');
        try{
            if(!isset($options['SMARTBILL_CIF']) || !isset($options['SMARTBILL_USER']) || !isset($options['SMARTBILL_API_TOKEN'])){
                throw new \Exception('Autentificare esuata. Va rugam verificati datele si incercati din nou.');
            }
            if(!isset($options['SMARTBILL_SYNC_STOCK']) || $options['SMARTBILL_SYNC_STOCK']!='1'){
                throw new \Exception('Setarea "Actualizeaza stocurile din magazinul online" nu este activa.');
            }

            if ($this->request->server['REQUEST_METHOD'] == 'POST'){
                $json = file_get_contents('php://input');
                $products=json_decode($json);

                $this->smartbill_opencart_stocks_update($products,$options['SMARTBILL_PRODUCT_SKU_TYPE'],$options['SMARTBILL_USED_STOCK']);
            }else{
                throw new \Exception('Invalid Method');
            }
        }catch(Exception $e){
            http_response_code(401);
            echo $e->getMessage();
        }
    }

    private function smartbill_opencart_stocks_update($products,$sku_type,$selected_stock){
        if (isset($products->products)){
            $products = $products->products;
        }
        if(is_array($products)){
            foreach($products as $product){
                if ($selected_stock && strtolower($product->warehouse) == strtolower($selected_stock)){
                    $product_id=null;
                    $has_options=false;
                    if(isset($product->productCode) && trim($product->productCode) != ""){
                        $product_id=$this->smartbill_opencart_get_product_by_sku($product->productCode,$sku_type);
                        if($product_id==false){
                            $product_id=$this->smartbill_opencart_get_product_by_name($product->productName);
                        }
                        if(preg_match("/(?=.*[;])(?=.*[(])(?=.*[)])/i", $product->productName)!==0){
                            $has_options=true;
                        }
                    }else {
                        //A product has options if in the product name you can find these caractres "(", ")", ";"
                        //Users must be informed to NOT use all of these caracters in the name of any product
                        if(preg_match("/(?=.*[;])(?=.*[(])(?=.*[)])/i", $product->productName)===0){
                           $product_id=$this->smartbill_opencart_get_product_by_name($product->productName); 
                        }else{
                            $product->productName=trim($product->productName);
                            $product->productName=substr($product->productName, 0, -1);
                            $product->productName=explode('(',$product->productName);
                            $product_options=trim($product->productName[1]);
                            $product_options=explode(';',$product_options);
                            $product->productName=trim($product->productName[0]);
                            $product_id=$this->smartbill_opencart_get_product_with_options_by_name($product->productName,$product_options);
                            $has_options=true;
                        }
                    } 
                   
                    if(!is_null($product_id) && $product_id!=false){ 
                        $product->quantity = filter_var($product->quantity, FILTER_SANITIZE_NUMBER_INT);
                        //Update stocks for product by product id
                        $this->db->query("UPDATE ".DB_PREFIX ."product SET `quantity` = '$product->quantity' WHERE `product_id` = '$product_id'");
                        
                        if($has_options){
                            //Update stocks for all options of the product by product id
                            $this->db->query("UPDATE ".DB_PREFIX ."product INNER JOIN ".DB_PREFIX ."product_option_value 
                            ON ".DB_PREFIX ."product.product_id = ".DB_PREFIX ."product_option_value.product_id 
                            SET ".DB_PREFIX ."product_option_value.quantity = $product->quantity
                            WHERE (((".DB_PREFIX ."product.product_id)=$product_id));");
                        }
                    }
                }
            }

           
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
}