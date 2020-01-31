<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');

class Api extends REST_Controller {
    public function __construct() {
       parent::__construct();
       $this->load->model('model');
    }
    public function index(){
        $this->load->view('welcome_message');
    }

    public function push_notification($data,$userid)
    {
        
        $andWhere = "userid=$userid";
        $userData = current($this->model->fetchQuery('fcm_id','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere));

        $fcm_id = $userData['fcm_id'];

        define('API_ACCESS_KEY', 'AAAAReSe04Y:APA91bG4MBvCZFQ74wobB3aMOlPY9h7dmOIa2BOUhTWz1uJaL-azPSuXE4FHE4h_3sPI7WW0jSs-XZfMaioOiPORAjUtBmH-_8uRcdwyD5qeCp1QmYMOiRqxU6oXkFAvDvX4UHBmj_o7');

        $paylod = array();
        $msg = array();
        
        if($data["device_type"] == 'ios')
        {
            $paylod["title"] = $data["title"];
            $paylod["body"]  = $data["body"];
            $paylod["sound"] = "default";
            if(isset($data["sub_text"])){
                $paylod["sub_text"] = $data["sub_text"];
            }
            if(isset($data["image_url"])){
                $paylod["image_url"] = $data["image_url"];
            }
            if(isset($data["link"])){
              $paylod["link"] = $data["link"];
            }
        }
        else
        {
          //android
            $paylod = array(
              "title"=>$data["title"],
              "body"=>$data["body"],
              "sub_text"=>$data["sub_text"],
            );
        }
        if($data["device_type"] == 'ios')
        {
            $msg["notification"] = $paylod;
            $msg["priority"]     = "high";
        }
        else
        {
            $msg["data"] = $paylod;    
        }

        $msg["registration_ids"] = array($fcm_id);
        //print_r($msg);die;
        $headers = array( 
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json'
        );
        
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_URL, 'https://gcm-http.googleapis.com/gcm/send');
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        // Set request method to POST       
        curl_setopt($ch, CURLOPT_POST, true);
        // Set custom request headers       
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // Get the response back as string instead of printing it       
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set JSON post data
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($msg));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Actually send the request    
        $result = curl_exec($ch);
        //print_r($result);
        // Handle errors 
        if (curl_errno($ch)) {
            echo 'GCM error: ' . curl_error($ch);
        }
        // Close curl handle
        curl_close($ch);
        /*print_r($result);
        die();*/
        //return json_decode($result);
        return $result;
        // Debug GCM response   
       // echo $apiKey;    
       // echo "<pre>"; 
        //print_r((array)json_decode($result)); 
       // die();
    }

    public function dynamicImageUpload($name,$folder,$path='')
    {
        $config['upload_path']          = $folder;
        $config['allowed_types']        = '*';        

        $this->load->library('upload',$config);
        $this->upload->initialize($config);

        if (!$this->upload->do_upload($name)){
            $error = $this->upload->display_errors();
            $resp = array('success' => '0', 'message' => $error); 
            @$this->response($resp);
            exit;

        }else{
            $data = $this->upload->data();
            return $folder.$data['file_name'];            
        }        
    }

    public function dynamicImageUploadMultiple($name,$folder,$path='')
    {         
        $imageArray = array();
        $ImageCount = count($_FILES[$name]['name']);
        
        for($i=0;$i<$ImageCount; $i++)
        {
            $_FILES['file']['name']       = $_FILES[$name]['name'][$i];
            $_FILES['file']['type']       = $_FILES[$name]['type'][$i];
            $_FILES['file']['tmp_name']   = $_FILES[$name]['tmp_name'][$i];
            $_FILES['file']['error']      = $_FILES[$name]['error'][$i];
            $_FILES['file']['size']       = $_FILES[$name]['size'][$i];

            $config['allowed_types']  = '*';
            $config['upload_path'] = $folder;

            $this->load->library('upload',$config);
            $this->upload->initialize($config);

            if($this->upload->do_upload('file'))
            {                
                $data = $this->upload->data();                
                $uploadImgData[$i][$name] = $data['file_name'];

                if(empty($path))
                    $imageArray[] = $uploadImgData[$i][$name];
                else
                    $imageArray[] = $path.$uploadImgData[$i][$name];
            }else{
                $error = $this->upload->display_errors();
                $resp = array('success' => false, 'message' => $error); 
                @$this->response($resp);
                exit;
            }
        }
        return $imageArray;

    }


    public function check_required_value($chk_params, $converted_array) {
        foreach ($chk_params as $param) {
            if (array_key_exists($param, $converted_array) && ($converted_array[$param] != '')) {
                $check_error = 0;
            } 
            else {
                $check_error = array('check_error' => 1, 'param' => $param);
                break;
            }
        }
        return $check_error;
    }
    public function qa_random_alphanum($length){
        $string='';
        while (strlen($string)<$length)
            $string.=str_pad(base_convert(mt_rand(0, 46655), 10, 36), 3, '0', STR_PAD_LEFT);
            return substr($string, 0, $length);
    }
    public function userRegister_post(){
        $pdata = file_get_contents("php://input");
        $data  = json_decode($pdata,true);
        $site_url=base_url();
        $required_parameter = array('handle','email','passcheck','createdip','fcm_id');
           $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
          $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
          @$this->response($resp);
        }
        $handle       = $data['handle'];
        $fcm_id       = $data['fcm_id'];
        $email        = $data['email'];
        $password     = $data['passcheck'];
        $createdip    = $data['createdip'];
        $where      = array(
          'handle'      => $handle
        );
        if(isset($password)){
           $string = '';    
           $salt = isset($password) ? $this->qa_random_alphanum(16) : null;
           $password = sha1(substr($salt, 0, 8).$data['passcheck'].substr($salt, 8));
        }
        else{
            $salt= null;    
            $password = null;
        }
        $check = $this->model->getAllwhere('qa_users', $where);
        if(!empty($check)){
            $resp = array('code' => '404', 'message' => 'User Already Registrered', 'response' => array('data' => 'This User  Name Already Registrered'));
            @$this->response($resp); 
        }
        $data = array(
            'handle'    => $handle,
            'email'     => $email,
            'passcheck' => hex2bin($password),//MD5($password),
            'passsalt'  => $salt,
            'createip'  => $createdip,
            'fcm_id'    => $fcm_id,
            'level'     => 0,   
            'credit'    => 0,
            'created'   => date('Y-m-d H:i:s')
        );
        $id = $this->model->insertData('qa_users', $data);
        $where = array('userid'=>$id);

        $result = $this->model->getsingle('qa_users', $where,'userid,email,handle,createip,created');
        if(!empty($result)) {

            $params = "email=$email level=0"; 
            $insert = array(
                'userid'=>$id,
                'handle'=>$handle,
                'event'=>'u_register',
                'params'=>$params,
                'ipaddress'=>'186.108.226.48',
                'datetime'=>date('Y-m-d H:i:s')
            );
            $this->model->insertQuery1('qa_eventlog',$insert);

            $resp = array('code' => '200', 'message' => 'Registrado correctamente.','response' => array('user'=> $result)); 
            @$this->response($resp);
        }
        else{
            $resp = array('code' => '404', 'message' => 'Error de servidor interno', 'response' => array('data' => 'Error de servidor interno'));
            @$this->response($resp);
        }
    }

    public function userLogin_post(){
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $site_url   = base_url();
        $required_parameter = array('handle','password','fcm_id');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' .strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $handle         = $data['handle'];
        $mypassword     = $data['password'];
        $fcm_id         = $data['fcm_id'];

        $where = array('handle'=>$handle);
        $data = $this->model->getsingle('qa_users', $where,'userid,email,handle,wallposts,passsalt,HEX(passcheck) AS passcheck,createip,created,uadblobid,uadimageurl');
        if(!empty($data)){
            $salt= $data->passsalt;
            $password = strtolower(sha1(substr($salt, 0, 8).$mypassword.substr($salt, 8)));
            strtolower($data->passcheck);//die;
            if($password == strtolower($data->passcheck))
            {                
                $insert = array(
                    'userid'=>$data->userid,
                    'handle'=>$handle,
                    'event'=>'u_login',
                    'params'=>'',
                    'ipaddress'=>'186.108.226.48',
                    'datetime'=>date('Y-m-d H:i:s')
                );
                $this->model->insertQuery1('qa_eventlog',$insert);

                $where = "userid=$data->userid";
                $update = array('fcm_id'=>$fcm_id);
                $this->model->updateQuery('qa_users',$update,$where);

                $userPnt = current($this->model->fetchQuery('points','qa_userpoints',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$where));
                $promotional_status = ($data->uadblobid==""||$data->uadimageurl=="")?'0':'1';
                
                $points = ($userPnt['points']>0)?$userPnt['points']:'0';
                $point_status = ($userPnt['points']>99)?'1':'0';
                $record = array(
                    'userid'=>$data->userid,
                    'handle'=>$handle,
                    'email'=> $data->email,
                    'wallposts'=>$data->wallposts,                    
                    'points'=>str_replace('opcion','opcion',"Actualmente tienes $points puntos y debe tener una cantidad de 100 puntos para seleccionar esta opcion."),
                    'point_status'=>$point_status,
                    'promotional_image_status'=>$promotional_status,
                    'created'=>$data->created,
                );
                
                $resp = array('code'=>'200','message'=>'SUCCESS','response'=>array('user'=>$record));
            }
            else{
                $resp = array('code' => '404', 'message' => 'Contraseña no válida, por favor vuelva a intentarlo', 'response' => array('message' => 'Contraseña no válida, por favor vuelva a intentarlo')); 
            }
        }
        else{
            $resp = array('code' => 'ERROR', 'message' => 'Este numero no esta registrado.', 'response' => array('message' => 'Este numero no esta registrado.'));
        }
        $this->response($resp);
    }



    public function getCategories_post(){
        $site_url = base_url();
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $allCategories = $this->model->getAll('qa_categories');
        if(empty($allCategories)){
            $resp = array('code' => '404', 'message' => 'Categoria de publicacion vacia', 'response' => array('postinfo' => 'Categoria de publicacion vacia'));
            $this->response($resp);  
        }
        else{
            $resp = array('code' => '200', 'message' => 'SUCCESS', 'response' => array('postinfo'=>$allCategories));   
            $this->response($resp);
        }
    }

    public function getCategoryByPost_post(){
        $site_url = base_url();
        $site_url = "https://adimvi.com/?qa=image&qa_blobid=";
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('categoryid','userid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $login_userid  = $data['userid'];
        $limit  = $data['limit'];
        $offset = $data['offset'];
        $categoryid = $data['categoryid'];
        $where = array(
            'categoryid'=> $data['categoryid']
        );
        $allposts = (Array)$this->model->getcategorybypost($categoryid,$limit,$offset);
        //print_r($allposts); die();
        foreach ($allposts as $value) 
        {
            
            if(($value['netvotes']>=0)){
                if(($value['netvotes']==0)){
                    $netVotes = $value['netvotes'];
                }else{
                    $netVotes = '+' .$value['netvotes'];
                }
            }else{
                $netVotes = $value['netvotes'];
            }

            $whereFav = array('userid'=>$login_userid,'entityid'=>$value['postid'],'entitytype'=>'Q');
            if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
                $post_favourite ='1';
            else
                $post_favourite ='0';

            $entityid = $data['userid'];
            $whereFollow = array('userid'=>$login_userid,'entityid'=>$value['postid'],'entitytype'=>'U','nouserevents'=>0);
            if(($this->model->countQuery('qa_userfavorites',$whereFollow))>0)
                $post_followup ='1';
            else
                $post_followup ='0';

            $whereMsg = array('parentid'=>$value['postid'],'type'=>'A');
            if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
                $total_message =$post_msg;
            else
                $total_message ='0';

            $whereBy = array('buyer'=>$login_userid,'postid'=>$value['postid']);
            if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                $post_buy ='1';
            else
                $post_buy ='0';

            $whereUser = array('userid'=>$login_userid);
            $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
            $credit = '$'.$userCredit['credit'];

            $whereMeta = array('postid'=>$value['postid']);
            $metaData = $this->model->fetchQuery('content','qa_postmetas',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereMeta,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);

            $meta_img = explode(" ",$metaData[0]['content']); 
            $extraImg = array();
            $countExtra =  count($meta_img);
            foreach($meta_img as $key=>$imgs){
                if(!empty($imgs))
                    $extraImg[] = array('url'.$key=>$imgs);
            }
            $rplc = array("\n","\"","<p>","</p>");
            $post_description= substr(str_replace($rplc,"",$metaData[1]['content']),0,300);

            $rLink = array(' ',"\n","\"","<p>","</p>");
            $post_titleSh = str_replace($rLink,"-",$value['title']);
            $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

            $title= str_replace($rplc,"",$value['title']);
            $result[] = array(
                'category_name' => $value['category_name'],
                'userid'        => $value['userid'],
                'postid'        => $value['postid'],
                'handle'        => ($value['handle'])?$value['handle']:'',
                'uadimageurl'   => ($value['uadimageurl'])?$value['uadimageurl']:'',
                'avatarblobid'  => ($value['avatarblobid'])?$value['avatarblobid']:'',
                'avatarwidth'   => ($value['avatarwidth'])?$value['avatarwidth']:'',
                'avatarheight'  => ($value['avatarheight'])?$value['avatarheight']:'',
                'title'         => ($title)?$title:'',
                'views'         => ($value['views'])?$value['views']:'',
                'comments'      => ($value['acount'])?$value['acount']:'',
                'netVotes'      => "$netVotes",
                'total_points'=>$value['points'].' Puntos',
                'post_favourite'=>$post_favourite,
                'post_followup'=>$post_followup,
                'total_message'=>$total_message,
                'share_link'=>$share_link,
                'credit'    => $credit,
                'price'     =>'$'.$value['price'],
                'pricer'    =>($value['pricer']>0)?'1':'0',
                'post_buy'  =>$post_buy,
                'post_image'    => $value['post_image'],
                'post_extra_image'=>$extraImg,
                'post_content'  => strip_tags((!empty($post_description))?$post_description:'')
                //'post_description'=>(!empty($post_description))?$post_description:'',                

            );
        }
        if(empty($result)){
            $resp = array('code' => '404', 'message' => 'Publicacion Vacía.', 'response' => array('sales' => 'Data Not Found..'));
            $this->response($resp);  
        }
        else{
            $resp = array('code' => '200', 'message' => 'Publicaciones de categoría particular', 'response' => array('posts'=>$result)); 
            $this->response($resp);
        }
        
    }

    public function getMostActivePost_post(){
        $site_url = base_url();
        
        $userid = $this->input->post('userid');
        $type = $this->input->post('type');
        $per_page = $this->input->post('per_page');
        $current_page = $this->input->post('current_page');        
        $data = array('userid'=>$userid,'type'=>$type,'per_page'=>$per_page,'current_page'=>$current_page);

        $required_parameter = array('userid','type','per_page','current_page');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $where = array('userid'=>$userid);

        if($current_page==0){
            $current_page=1;
            $offset=0;
        }
        elseif($current_page==1){
            $offset=0;
        }
        else{
           $offset= ( $per_page * $current_page)- $per_page;
        }   
        $limit=  $per_page * $current_page;
        if($type == 0){
            $today=date('Y-m-d');
            $allPostData= $this->model->getAllMostActivePost($start_date='',$end_date='',$today,$limit,$offset);
        }
        elseif ($type == 1) {
          $monday = strtotime("last monday");
          $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
          $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
          $this_week_sd = date("Y-m-d",$monday);
          $this_week_ed = date("Y-m-d",$sunday);
          $start_date = $this_week_sd;
          $end_date = $this_week_ed;
          $allPostData= $this->model->getAllMostActivePost($start_date,$end_date,'',$limit,$offset);
        }
        elseif ($type == 2) {
            $ddate = date("Y-m-d");
            $date = new DateTime($ddate);
            $week_num = $date->format("W");
            $month = $date->format("M");
            $year = $date->format("Y-m-d");                
            $today=date('Y-m-d');
            $end_date= date('Y-m-d', strtotime($today. ' - 30 days'));
            $allPostData= $this->model->getAllMostActivePost($end_date,$today,'',$limit,$offset);
        }
        else{
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('posts' => 'Datos no encontrados.'));
            $this->response($resp);  
        }
        if(empty($allPostData)){  
             $resp = array('code' => '404', 'message' => 'Empty Posts', 'response' => array('posts' => 'Datos no encontrados.'));
            $this->response($resp);   
        }
        else {
            foreach ($allPostData as $value) {
                $rLink = array(' ',"\n","\"","<p>","</p>");
                $post_titleSh = str_replace($rLink,"-",$value['title']);
                $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

                $whereBy = array('buyer'=>$userid,'postid'=>$value['postid']);
                if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                    $post_buy ='1';
                else
                    $post_buy ='0';

                $whereUser = array('userid'=>$userid);
                $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
                $credit = '$'.$userCredit['credit'];

                $rplc = array("\n","\"","<p>","</p>");
                $post_description= str_replace($rplc,"",$value['post_content']);
                $result[] = array(
                    'category_name' => $value['category_name'],
                    'userid'        => $value['userid'],
                    'postid'        => $value['postid'],
                    'handle'        => $value['handle'],
                    'uadimageurl'   => $value['uadimageurl'],
                    'avatarblobid'  => $value['avatarblobid'],
                    'avatarwidth'   => $value['avatarwidth'],
                    'avatarheight'  => $value['postid'],
                    'title'         => $value['title'],
                    'upvotes'       => $value['upvotes'],
                    'views'         => $value['views'],
                    'comments'      => $value['acount'],
                    'like'          => $value['netvotes'],
                    'post_image'    => $value['post_image'],
                    'categoryid' => $value['categoryid'],
                    'share_link'=>$share_link,
                    'credit'    => $credit,
                    'price'     =>'$'.$value['price'],
                    'pricer'    =>($value['pricer']>0)?'1':'0',
                    'post_buy'  =>$post_buy,
                    'post_content'  => $post_description
                );
            } 
            $resp = array('code' => '200', 'message' => 'Publicaciones activas', 'response' => array('posts'=>$result));   
            $this->response($resp); 
        }
    }

    public function getRecentPost_post(){
        $site_url = base_url();
        $userid = $this->input->post('userid');

        $type = $this->input->post('type');
        $per_page = $this->input->post('per_page');
        $current_page = $this->input->post('current_page');
        
        $data = array('userid'=>$userid,'type'=>$type,'per_page'=>$per_page,'current_page'=>$current_page);        
        $required_parameter = array('userid','type','per_page','current_page');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        //$type = $data['type'];
        //$per_page = $data['per_page'];
        //$current_page = $data['current_page'];
        if($current_page==0){
            $current_page=1;
            $offset=0;
        }
        elseif($current_page==1){
            $offset=0;
        }
        else{
           $offset= ($per_page * $current_page)- $per_page;
        }   
        $limit=  $per_page * $current_page;
        if($type == 0){
            $today=date('Y-m-d');
            $allPostData= $this->model->getAllRecentPostRecord($start_date='',$end_date='',$today,$limit,$offset);
        }
        elseif ($type == 1) {
          $monday = strtotime("last monday");
          $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
          $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
          $this_week_sd = date("Y-m-d",$monday);
          $this_week_ed = date("Y-m-d",$sunday);
          $start_date = $this_week_sd;
          $end_date = $this_week_ed;
          $allPostData= $this->model->getAllRecentPostRecord($start_date,$end_date,'',$limit,$offset);          
        }
        elseif ($type == 2) {
            $ddate = date("Y-m-d");
            $date = new DateTime($ddate);
            $week_num = $date->format("W");
            $month = $date->format("M");
            $year = $date->format("Y-m-d");                
            $today=date('Y-m-d');
            $end_date= date('Y-m-d', strtotime($today. ' - 30 days'));
            $allPostData= $this->model->getAllRecentPostRecord($end_date,$today,'',$limit,$offset);
        }
        else{
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('posts' => 'Datos no encontrados.'));
            $this->response($resp);  
        }
        if(empty($allPostData)){   
            $resp = array('code' => '404', 'message' => 'Empty Posts', 'response' => array('posts' => 'Data Not Found..'));
            $this->response($resp);   
        }
        else {
            foreach ($allPostData as $value) {
                $rLink = array(' ',"\n","\"","<p>","</p>");
                $post_titleSh = str_replace($rLink,"-",$value['title']);
                $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

                $whereBy = array('buyer'=>$userid,'postid'=>$value['postid']);
                if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                    $post_buy ='1';
                else
                    $post_buy ='0';

                $whereUser = array('userid'=>$userid);
                $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
                $credit = '$'.$userCredit['credit'];

                $rplc = array("\n","\"","<p>","</p>");
                $post_description= str_replace($rplc,"",$value['post_content']);
                $result[] = array(
                    'category_name' => $value['category_name'],
                    'userid'        => $value['userid'],
                    'postid'        => $value['postid'],
                    'handle'        => $value['handle'],
                    'uadimageurl'   => $value['uadimageurl'],
                    'avatarblobid'  => $value['avatarblobid'],
                    'avatarwidth'   => $value['avatarwidth'],
                    'avatarheight'  => $value['postid'],
                    'title'         => $value['title'],
                    'upvotes'       => $value['upvotes'],
                    'views'         => $value['views'],
                    'comments'      => $value['acount'],
                    'like'          => $value['netvotes'],
                    'post_image'    => $value['post_image'],
                    'categoryid' => $value['categoryid'],
                    'share_link'=>$share_link,
                    'credit'    => $credit,
                    'price'     =>'$'.$value['price'],
                    'pricer'    =>($value['pricer']>0)?'1':'0',
                    'post_buy'  =>$post_buy,
                    'post_content'  => $post_description
                );
            } 
            $resp = array('code' => '200', 'message' => 'Recent Posts', 'response' => array('posts'=>$result));   
            $this->response($resp); 
        }
    }


    public function getMostVotedPost_post(){
        $site_url = base_url();
        //$pdata      = file_get_contents("php://input");
        //$data       = json_decode($pdata,true);

        $userid = $this->input->post('userid');
        $type = $this->input->post('type');
        $per_page = $this->input->post('per_page');
        $current_page = $this->input->post('current_page');        
        $data = array('userid'=>$userid,'type'=>$type,'per_page'=>$per_page,'current_page'=>$current_page);

        $required_parameter = array('userid','type','per_page','current_page');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $where = array('userid'=>$userid);
        //$type = $data['type'];
        //$per_page = $data['per_page'];
        //$current_page = $data['current_page'];
        if($current_page==0){
            $current_page=1;
            $offset=0;
        }
        elseif($current_page==1){
            $offset=0;
        }
        else{
           $offset= ( $per_page * $current_page)- $per_page;
        }   
        $limit=  $per_page * $current_page;
        if($type == 0){
            $today=date('Y-m-d');
            $allPostData= $this->model->getMostVotedPostRecord($start_date='',$end_date='',$today,$limit,$offset);
        }
        elseif ($type == 1) {
          $monday = strtotime("last monday");
          $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
          $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
          $this_week_sd = date("Y-m-d",$monday);
          $this_week_ed = date("Y-m-d",$sunday);
          $start_date = $this_week_sd;
          $end_date = $this_week_ed;
          $allPostData= $this->model->getMostVotedPostRecord($start_date,$end_date,'',$limit,$offset);
        }
        elseif ($type == 2) {
            $ddate = date("Y-m-d");
            $date = new DateTime($ddate);
            $week_num = $date->format("W");
            $month = $date->format("M");
            $year = $date->format("Y-m-d");                
            $today=date('Y-m-d');
            $end_date= date('Y-m-d', strtotime($today. ' - 30 days'));
            $allPostData= $this->model->getMostVotedPostRecord($end_date,$today,'',$limit,$offset);
        }
        else{
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('posts' => 'Datos no encontrados.'));
            $this->response($resp);  
        }
        if(empty($allPostData)){   
            $resp = array('code' => '404', 'message' => 'Empty Posts', 'response' => array('posts' => 'Data Not Found..'));
            $this->response($resp);   
        }        
        else {

            foreach ($allPostData as $value) {
               
                $rLink = array(' ',"\n","\"","<p>","</p>");
                $post_titleSh = str_replace($rLink,"-",$value['title']);
                $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

                $whereBy = array('buyer'=>$userid,'postid'=>$value['postid']);
                if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                    $post_buy ='1';
                else
                    $post_buy ='0';

                $whereUser = array('userid'=>$userid);
                $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
                $credit = '$'.$userCredit['credit'];

                
                $rplc = array("\n","\"","<p>","</p>");
                $post_description= str_replace($rplc,"",$value['post_content']);
                $result[] = array(
                    'category_name' => $value['category_name'],
                    'userid'        => $value['userid'],
                    'postid'        => $value['postid'],
                    'handle'        => $value['handle'],
                    'uadimageurl'   => $value['uadimageurl'],
                    'avatarblobid'  => ($value['avatarblobid'])?$value['avatarblobid']:'',
                    'avatarwidth'   => ($value['avatarwidth'])?$value['avatarwidth']:'',
                    'avatarheight'  => $value['postid'],
                    'title'         => $value['title'],
                    'upvotes'       => $value['upvotes'],
                    'views'         => $value['views'],
                    'comments'      => $value['acount'],
                    'like'          => $value['netvotes'],
                    'post_image'    => $value['post_image'],                    
                    'categoryid' => $value['categoryid'],
                    'share_link'=>$share_link,
                    'credit'    => $credit,
                    'price'     =>'$'.$value['price'],
                    'pricer'    =>($value['pricer']>0)?'1':'0',
                    'post_buy'  =>$post_buy,
                    'post_content'  => substr($post_description,0,300)
                );
            } 
            $resp = array('code' => '200', 'message' => 'Publicaciones más votadas', 'response' => array('posts'=>$result)); 
            $this->response($resp); 
        }
    }

    public function getfavourite_post(){
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $userid      = $data['userid'];
        $where = array('userid'=>$userid);

        $profile = $this->model->getfavourite($userid);
        //print_r($profile); die();

        $holeData = array();
        foreach($profile as $key=>$profiles)
        {
            if(($profiles['netvotes']>=0)){
                if(($profiles['netvotes']==0)){
                    $netVotes = $profiles['netvotes'];
                }else{
                    $netVotes = '+' .$profiles['netvotes'];
                }
            }else{
                $netVotes = $profiles['netvotes'];
            }

            
            $post_date = date('d M',strtotime($profiles['post_date']));
            $post_time = date('i',strtotime($profiles['post_date'])).' min.';
            if(!empty($profiles['avatarblobid'])){
                $avatarblobid = $profiles['avatarblobid'];
            }else{
                $avatarblobid = '';
            }

            $whereMsg = array('parentid'=>$profiles['postid'],'type'=>'A');
            if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
                $total_message =$post_msg;
            else
                $total_message ='0';

            $login_userid = $data['userid'];
            $whereBy = array('buyer'=>$login_userid,'postid'=>$profiles['postid']);
            if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                $post_buy ='1';
            else
                $post_buy ='0';

            $whereUser = array('userid'=>$login_userid);
            $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
            $credit = '$'.$userCredit['credit'];


            $rLink = array(' ',"\n","\"","<p>","</p>");
            $post_titleSh = str_replace($rLink,"-",$profiles['title']);
            $share_link = 'https://www.adimvi.com/?qa='.$profiles['postid'].'/'.strtolower($post_titleSh);

            $data = array(
                "avatarblobid"=>$avatarblobid,
                "postid"=>$profiles['postid'],
                "userid"=>$profiles['userid'],                
                "username"=>$profiles['handle'],
                'netvotes'=>"$netVotes",
                "post_title"=>$profiles['title'],
                "post_description"=>'It is never too late to shine. Many times we focus on what we would like to be and not on what we are. See you in the mirror, and yet ...',
                'post_image'=>$profiles['post_image'],
                'total_message'=>$total_message,            
                "views"=>$profiles['views'],
                "postid"=>$profiles['postid'],
                "categoyname"=>$profiles['categoyname'],
                'post_date'=>$post_date,
                'post_time'=>$post_time,            
                'share_link'=>$share_link,
                'credit'    => $credit,
                'price'     =>'$'.$profiles['price'],
                'pricer'    =>($profiles['pricer']>0)?'1':'0',
                'post_buy'  =>$post_buy
            );

            $holeData[] = $data;
        }

        if(empty($profiles)){
            $resp = array('code' => 'ERROR', 'message' => 'Failure', 'response' => array('message' => 'No hay usuarios favoritos.'));
            @$this->response($resp);   
        }
        else {
            $resp = array('code' => 'SUCCESS', 'message' => 'SUCCESS', 'response' => array('favourite' => $holeData));   
            $this->response($resp); 
        }
    }
    public function setfavourite_post()
    {
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid','postid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $userid      = $data['userid'];
        $postid      = $data['postid'];
        $where = array(
            'userid' => $userid,
            'entityid' => $postid,
            'entitytype' => 'Q'
        );
        $profiles = $this->model->getAllwhere('qa_userfavorites',$where);
        if(empty($profiles))
        {
            $data = array(
                'userid' => $userid,
                'entitytype' => 'Q',
                'entityid' => $postid,
                'nouserevents' => 0
            );
            $this->model->insertData('qa_userfavorites',$data);
            $resp = array('status'=>1,'code'=>'SUCCESS','message'=>'Favorito con exito.');

            $insert = array(
                'userid'=>$userid,
                'handle'=>'username',
                'event'=>'q_favorite',
                'params'=>$postid,
                'ipaddress'=>'186.108.226.48',
                'datetime'=>date('Y-m-d H:i:s')
            );
            $this->model->insertQuery1('qa_eventlog',$insert);            
        }
        else{
            $profiles[0]->nouserevents;                        
            $this->model->deleteQuery('qa_userfavorites',$where);
            $resp = array('status'=>0,'code'=>'SUCCESS','message'=>'Favorito eliminado.');   
        }
        $this->response($resp);
    }

    public function getuser_post(){
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $userid  = $data['userid'];
        $where= array('userid'=>$userid);
        $data = (Array)$this->model->getsingle('qa_users',$where);
        echo $password = base64_encode($data['passcheck']);
        echo '<br/>';
        die;
    }

    public function getAllPosts_post(){
        $site_url = base_url();
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid','type','per_page','current_page');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $type = $data['type'];
        $per_page = $data['per_page'];
        $current_page = $data['current_page'];
        if($current_page==0){
            $current_page=1;
            $offset=0;
        }
        elseif($current_page==1){
            $offset=0;
        }
        else{
           $offset= ( $per_page * $current_page)- $per_page;
        }   
        $limit=  $per_page * $current_page;

        if($type == 0){
            $today=date('Y-m-d H:i:s');
            $allPostData= $this->model->GetAllPostRecord($start_date,$end_date,'',$limit,$offset);
        }
        elseif ($type == 1) {
          $monday = strtotime("last monday");
          $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
          $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
          $this_week_sd = date("Y-m-d",$monday);
          $this_week_ed = date("Y-m-d",$sunday);
          $start_date = $this_week_sd;
          $end_date = $this_week_ed;
          $allPostData= $this->model->GetAllPostRecord($start_date,$end_date,'',$limit,$offset);
        }
        elseif ($type == 2) {
            $ddate = date("Y-m-d H:i:s");
            $date = new DateTime($ddate);
            $week_num = $date->format("W");
            $month = $date->format("M");
            $year = $date->format("Y-m-d");                
            $today=date('Y-m-d H:i:s');
            $end_date= date('Y-m-d H:i:s', strtotime($today. ' - 90 days'));
            $allPostData= $this->model->GetAllPostRecord($end_date,$today,'',$limit,$offset);
        }
        else{
            echo "Data Not Found";
        }
        if(empty($allPostData)){   
            $resp = array('code' => '404', 'message' => 'Todos los datos de publicaciones.', 'response' => array('allpost' => 'Datos no encontrados.'));
            $this->response($resp);   
        }
        else {
            $resp = array('code' => '200', 'message' => 'SUCCESS', 'response' => array('allpost'=>$allPostData,'img_url' => $site_url.'asset/uploads/'));   
            $this->response($resp); 
        }
    }

    public function getExploereData_post(){
        $site_url = base_url();
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $activeData = $this->model->getMoreActive();
        print_r($activeData);
        die;
    }

    public function getsales_post(){
        $site_url = base_url();
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
 
        $where= array('seller'=>$data['userid']);
        $allsales = (Array)$this->model->GetJoinRecord('qa_adimvipre','postid','qa_posts','postid','qa_adimvipre.buyer,qa_adimvipre.postid,qa_adimvipre.price,qa_adimvipre.created,qa_posts   .title',$where);

        foreach($allsales as $key => $sale){
           $where = array(
            'userid'        =>$sale->buyer
           ); 
           $result = $this->model->getsingle('qa_users', $where);
           $allsales[$key]->handle=$result->handle;
        }
        if(empty($allsales)){
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('sales' => 'Datos no encontrados.'));
            $this->response($resp);  
        }
        else{
            $resp = array('code' => '200', 'message' => 'Sales list as follows', 'response' => array('sales'=>$allsales));   
            $this->response($resp);
        }
    }

    public function getallmember_post(){
        $site_url = base_url();
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('offset','limit');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $pagination = array(
            'offset' => $data['offset'],
            'limit'  => $data['limit']
           ); 
        $members = (Array)$this->model->getallmember($pagination);
        if(empty($members)){
            $resp = array('code' => '404', 'message' =>'Datos no encontrados.', 'response' => array('sales' => 'Datos no encontrados.'));
            $this->response($resp);  
        }
        else{
            $resp = array('code' => '200', 'message' => 'Lista de todos los miembros.', 'response' => array('sales'=>$members)); 
            $this->response($resp);
        }
        
    } 

    public function getParticularUserPost_post(){
        $site_url = base_url();
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $where = array('userid'=>$data['userid']);
        $allposts = (Array)$this->model->getAllwhere('qa_posts',$where,'postid,title,content,upvotes,downvotes');
        //print_r($allposts);die;
        if(empty($allposts)){
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('sales' => 'Datos no encontrados.'));
            $this->response($resp);  
        }
        else{
            $resp = array('code' => '200', 'message' => 'Particular Member Posts', 'response' =>array('posts'=>$allposts));
            $this->response($resp);
        }
    } 

    public function OLDgetFollowing_post()
    {
        $site_url = base_url();
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('entitytype','userid');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $where = array(
            'qa_userfavorites.userid'     => $data['userid'],
            'qa_userfavorites.entitytype' => $data['entitytype']
        );
        $userfavorites = (Array)$this->model->GetJoinRecord('qa_userfavorites','entityid','qa_posts','userid','qa_userfavorites.entityid,qa_posts.title,qa_posts.content,qa_posts.views,qa_posts.upvotes,qa_posts.downvotes,qa_posts.netvotes',$where);
        if(empty($userfavorites)){
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('sales' => 'Datos no encontrados.'));
            $this->response($resp);  
        }
        else{
            $resp = array('code' => '200', 'message' => 'Particular Favorites', 'response' => array('userfavorites'=>$userfavorites)); 
            $this->response($resp);
        }
    }  
    
    /*
    public function getMostVotedPost_post(){
        $site_url = base_url();
        $pdata      = file_get_contents("php://input");
        $data       = json_decode($pdata,true);
        $required_parameter = array('userid','type','per_page','current_page');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $where = array(
            'userid'    => $data['userid']
        );
        $type = $data['type'];
        $per_page = $data['per_page'];
        $current_page = $data['current_page'];
        if($current_page==0){
            $current_page=1;
            $offset=0;
        }
        elseif($current_page==1){
            $offset=0;
        }
        else{
           $offset= ( $per_page * $current_page)- $per_page;
        }   
        $limit=  $per_page * $current_page;
        if($type == 0){
            $today=date('Y-m-d H:i:s');
            $allPostData= $this->model->getMostVotedPostRecord($start_date,$end_date,'',$limit,$offset);
        }
        elseif ($type == 1) {
          $monday = strtotime("last monday");
          $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
          $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
          $this_week_sd = date("Y-m-d",$monday);
          $this_week_ed = date("Y-m-d",$sunday);
          $start_date = $this_week_sd;
          $end_date = $this_week_ed;
          $allPostData= $this->model->getMostVotedPostRecord($start_date,$end_date,'',$limit,$offset);
        }
        elseif ($type == 2) {
            $ddate = date("Y-m-d H:i:s");
            $date = new DateTime($ddate);
            $week_num = $date->format("W");
            $month = $date->format("M");
            $year = $date->format("Y-m-d");                
            $today=date('Y-m-d H:i:s');
            $end_date= date('Y-m-d H:i:s', strtotime($today. ' - 90 days'));
            $allPostData= $this->model->getMostVotedPostRecord($end_date,$today,'',$limit,$offset);
        }
        else{
            $resp = array('code' => '404', 'message' => 'Data Not Found', 'response' => array('allpost' => 'Data Not Found..'));
            $this->response($resp);  
        }
        if(empty($allPostData)){   
            $resp = array('code' => '404', 'message' => 'Empty Posts', 'response' => array('allpost' => 'Data Not Found..'));
            $this->response($resp);   
        }
        else {
            $resp = array('code' => '200', 'message' => 'Most Voted Posts', 'response' => array('allpost'=>$allPostData));   
            $this->response($resp); 
        }
    }
    */

    public function getMostViewsPost_post(){
        $site_url = base_url();
        //$pdata      = file_get_contents("php://input");
        //$data       = json_decode($pdata,true);
        $userid = $this->input->post('userid');
        $type = $this->input->post('type');
        $per_page = $this->input->post('per_page');
        $current_page = $this->input->post('current_page');        
        $data = array('userid'=>$userid,'type'=>$type,'per_page'=>$per_page,'current_page'=>$current_page);

        $required_parameter = array('userid','type','per_page','current_page');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $where = array('userid'=>$userid);
        // $type = $data['type'];
        // $per_page = $data['per_page'];
        // $current_page = $data['current_page'];
        if($current_page==0){
            $current_page=1;
            $offset=0;
        }
        elseif($current_page==1){
            $offset=0;
        }
        else{
           $offset= ( $per_page * $current_page)- $per_page;
        }   
        $limit=  $per_page * $current_page;
        if($type == 0){
            $today=date('Y-m-d');
            $allPostData= $this->model->getMostVotedPostRecord($start_date='',$end_date='',$today,$limit,$offset);
        }
        elseif ($type == 1) {
          $monday = strtotime("last monday");
          $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
          $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
          $this_week_sd = date("Y-m-d",$monday);
          $this_week_ed = date("Y-m-d",$sunday);
          $start_date = $this_week_sd;
          $end_date = $this_week_ed;
          $allPostData= $this->model->getMostVotedPostRecord($start_date,$end_date,'',$limit,$offset);
        }
        elseif ($type == 2) {
            $ddate = date("Y-m-d H:i:s");
            $date = new DateTime($ddate);
            $week_num = $date->format("W");
            $month = $date->format("M");
            $year = $date->format("Y-m-d");                
            $today=date('Y-m-d');
            $end_date= date('Y-m-d', strtotime($today. ' - 90 days'));
            $allPostData= $this->model->getMostVotedPostRecord($end_date,$today,'',$limit,$offset);  
        }
        else{
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('allpost' => 'Datos no encontrados.'));
            $this->response($resp);  
        }
        if(empty($allPostData)){   
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('allpost' => 'Datos no encontrados.'));
            $this->response($resp);   
        }
        else {

            foreach ($allPostData as $value) {
                $rLink = array(' ',"\n","\"","<p>","</p>");
                $post_titleSh = str_replace($rLink,"-",$value['title']);
                $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

                $whereBy = array('buyer'=>$userid,'postid'=>$value['postid']);
                if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                    $post_buy ='1';
                else
                    $post_buy ='0';

                $whereUser = array('userid'=>$userid);
                $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
                $credit = '$'.$userCredit['credit'];

                $rplc = array("\n","\"","<p>","</p>");
                $post_description= str_replace($rplc,"",$value['post_content']);
                $result[] = array(
                    'category_name' => $value['category_name'],
                    'userid'        => $value['userid'],
                    'postid'        => $value['postid'],
                    'handle'        => $value['handle'],
                    'uadimageurl'   => $value['uadimageurl'],
                    'avatarblobid'  => $value['avatarblobid'],
                    'avatarwidth'   => $value['avatarwidth'],
                    'avatarheight'  => $value['postid'],
                    'title'         => $value['title'],
                    'upvotes'       => $value['upvotes'],
                    'views'         => $value['views'],
                    'comments'      => $value['acount'],
                    'like'          => $value['netvotes'],
                    'post_image'    => $value['post_image'],
                    'categoryid' => $value['categoryid'],
                    'share_link'=>$share_link,
                    'credit'    => $credit,
                    'price'     =>'$'.$value['price'],
                    'pricer'    =>($value['pricer']>0)?'1':'0',
                    'post_buy'  =>$post_buy,
                    'post_content'  => $post_description
                );
            } 


            $resp = array('code' => '200', 'message' => 'Most View Posts', 'response' => array('posts'=>$result));   
            $this->response($resp); 
        }
    }



    public function getMostCommentedPost_post(){
        $site_url = base_url();
        //$pdata      = file_get_contents("php://input");
        //$data       = json_decode($pdata,true);
        $userid = $this->input->post('userid');
        $type = $this->input->post('type');
        $per_page = $this->input->post('per_page');
        $current_page = $this->input->post('current_page');        
        $data = array('userid'=>$userid,'type'=>$type,'per_page'=>$per_page,'current_page'=>$current_page);

        $required_parameter = array('userid','type','per_page','current_page');
        $chk_error = $this->check_required_value($required_parameter,$data);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $where = array('userid'=>$userid);

        if($current_page==0){
            $current_page=1;
            $offset=0;
        }
        elseif($current_page==1){
            $offset=0;
        }
        else{
           $offset= ( $per_page * $current_page)- $per_page;
        }   
        $limit=  $per_page * $current_page;
        if($type == 0){
            $today=date('Y-m-d');
            $allPostData= $this->model->getMostCommentedPostRecord($start_date='',$end_date='',$today,$limit,$offset,$userid);
        }
        elseif ($type == 1) {
          $monday = strtotime("last monday");
          $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
          $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
          $this_week_sd = date("Y-m-d",$monday);
          $this_week_ed = date("Y-m-d",$sunday);
          $start_date = $this_week_sd;
          $end_date = $this_week_ed;
          $allPostData= $this->model->getMostCommentedPostRecord($start_date,$end_date,'',$limit,$offset,$userid);
        }
        elseif ($type == 2) {
            $ddate = date("Y-m-d");
            $date = new DateTime($ddate);
            $week_num = $date->format("W");
            $month = $date->format("M");
            $year = $date->format("Y-m-d");                
            $today=date('Y-m-d');
            $end_date= date('Y-m-d', strtotime($today. ' - 30 days'));
            $allPostData= $this->model->getMostCommentedPostRecord($end_date,$today,'',$limit,$offset,$userid);  
        }
        else{
            $resp = array('code' => '404', 'message' => 'Type not match', 'response' => array('posts' => 'El tipo no coincide.'));
            $this->response($resp);  
        }
        if(empty($allPostData)){   
            $resp = array('code' => '404', 'message' => 'Empty Posts', 'response' => array('posts' => 'Datos no encontrados.'));
            $this->response($resp);   
        }
        else {

            $holeData = array();
            foreach($allPostData as $data)
            {
                $dbpostid = $data['parentid'];

                $value = $this->model->postDataGet($dbpostid);
                //print_r($value); die();            
                if($value['userid']>0)
                {
                    $rLink = array("\n","\"","<p>","</p>");
                    $post_titleSh = str_replace($rLink,"-",$value['post_title']);
                    $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

                    $whereBy = array('buyer'=>$userid,'postid'=>$value['postid']);
                    if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                        $post_buy ='1';
                    else
                        $post_buy ='0';

                    $whereUser = array('userid'=>$userid);
                    $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
                    $credit = '$'.$userCredit['credit'];

                    $rplc = array("\n","\"","<p>","</p>");
                    $post_description= str_replace($rplc,"",$value['post_content']);
                    $holeData[] = array(
                        'category_name' => $value['category_name'],
                        'userid'        => $value['userid'],
                        'postid'        => $value['postid'],
                        'handle'        => $value['username'],
                        //'uadimageurl'   => $value['uadimageurl'],
                        'avatarblobid'  => ($value['avatarblobid'])?$value['avatarblobid']:'',
                        //'avatarwidth'   => $value['avatarwidth'],
                        //'avatarheight'  => $value['postid'],
                        'title'         => str_replace($rLink,"",$value['post_title']),
                        'upvotes'       => $value['upvotes'],
                        'views'         => $value['views'],                        
                        'like'          => $value['netvotes'],
                        'post_image'    => $value['post_image'],
                        //'post_content'  => strip_tags($value['post_content']),
                        'categoryid' => $value['categoryid'],
                        'share_link'=>$share_link,
                        'credit'    => $credit,
                        'price'     =>'$'.$value['price'],
                        'pricer'    =>($value['pricer']>0)?'1':'0',
                        'post_buy'  =>$post_buy,
                        'post_content' => $post_description
                    );
                }
                
            }
            $resp = array('code' => '200', 'message' => 'Publicaciones más comentadas.', 'response' => array('posts'=>$holeData));   
            $this->response($resp); 
        }
    }
    
    // Start code by ashvin patidar
    public function profileDataUpdate($user_id,$links_websites,$social_networks,$full_name,$location,$about_me)
    {
        $where = array('userid' => $user_id,'title'=>'website');
        $data  = array('title'=>'website','content'=>$links_websites,'userid'=>$user_id);
        $this->model->updateQuery("qa_userprofile",$data,$where);

        $where = array('userid' => $user_id,'title'=>'social-networks');
        $data  = array('title'=>'social-networks','content'=>$social_networks,'userid'=>$user_id);
        $this->model->updateQuery("qa_userprofile",$data,$where);

        $where = array('userid' => $user_id,'title'=>'name');
        $data  = array('title'=>'name','content'=>$full_name,'userid'=>$user_id);
        $this->model->updateQuery("qa_userprofile",$data,$where);

        $where = array('userid' => $user_id,'title'=>'location');
        $data  = array('title'=>'location','content'=>$location,'userid'=>$user_id);
        $this->model->updateQuery("qa_userprofile",$data,$where);

        $where = array('userid' => $user_id,'title'=>'about');
        $data  = array('title'=>'about','content'=>$about_me,'userid'=>$user_id);
        $this->model->updateQuery("qa_userprofile",$data,$where);
        return 1;
    }

    public function profileDataInsert($user_id,$links_websites,$social_networks,$full_name,$location,$about_me)
    {
        $data  = array('title'=>'website','content'=>$links_websites,'userid'=>$user_id);
        $this->model->insertQuery("qa_userprofile",$data);

        $data  = array('title'=>'social-networks','content'=>$social_networks,'userid'=>$user_id);
        $this->model->insertQuery("qa_userprofile",$data);

        $data  = array('title'=>'name','content'=>$full_name,'userid'=>$user_id);
        $this->model->insertQuery("qa_userprofile",$data);

        $data  = array('title'=>'location','content'=>$location,'userid'=>$user_id);
        $this->model->insertQuery("qa_userprofile",$data);

        $data  = array('title'=>'about','content'=>$about_me,'userid'=>$user_id);
        $this->model->insertQuery("qa_userprofile",$data);
        return 1;
    }

    public function imageBlobFile($name)
    {
        is_uploaded_file($_FILES[$name]['tmp_name']);
        $blobImage = addslashes(file_get_contents($_FILES[$name]['tmp_name']));
        $imageProperties = getimageSize($_FILES[$name]['tmp_name']);

        $width = $imageProperties[0];
        $height = $imageProperties[1];
        $imageType = str_replace('image/',"",$imageProperties['mime']);

        $data = array('width'=>$width,'height'=>$height,'type'=>$imageType,'blob'=>$blobImage);
        return $data;
    }

    public function checkUserID($user_id){
        $where = array('userid' => $user_id); 
        if(($this->model->countQuery('qa_users',$where))<=0){
            $resp = array('code' => '400', 'message' => 'Invalid user id');   
            $this->response($resp); exit;
        }
    }

    public function checkUserEmail($user_id,$email){
        $where = array('userid!=' => $user_id,'email'=>$email);
        if(($this->model->countQuery('qa_users',$where))>0){
            $resp = array('code' => '400', 'message' => 'La identificacion del correo electronico ya existe.');   
            $this->response($resp); exit;
        }
    }

    public function checkUserName($user_id,$username){
        $where = array('userid!=' => $user_id,'handle'=>$username);
        if(($this->model->countQuery('qa_users',$where))>0){
            $resp = array('code' => '400', 'message' => 'El nombre de usuario ya existe.');   
            $this->response($resp); exit;
        }
    }
    
    // ashvin code
    public function editProfile_post()
    {
        date_default_timezone_set('Asia/Kolkata');
        $created = date("Y-m-d H:i:s");

        $user_id = $this->input->post('user_id');
        $email = $this->input->post('email'); 
        $username = $this->input->post('handle');
        $paypal = $this->input->post('paypal');
        $private_messages = $this->input->post('private_messages');
        $publications_wall = $this->input->post('publications_wall');
        $uadimageurl = $this->input->post('uadimageurl');
        $subscribe_email = $this->input->post('subscribe_email');

        $checkData = array('user_id'=>$user_id,'email'=>$email,'handle'=>$username);

        $required_parameter = array('user_id','email','handle');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . strtoupper($chk_error['param']));
            @$this->response($resp);
        }
        $this->checkUserID($user_id);
        $this->checkUserEmail($user_id,$email);
        $this->checkUserName($user_id,$username);

        $whereUserid = array('userid'=>$user_id);
        $select = 'avatarblobid,avatarwidth,avatarheight,coverblobid,coverwidth,coverheight,uadblobid,uadwidth,uadheight,uadimageurl';
        $userData = current($this->model->fetchQuery($select,'qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUserid,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=0,$eLimit=1));
        
        // Start profile image update OR insert conditions by Ashvin
        $uadblobid = str_shuffle('1324567890561218519'); 
        if(!empty($_FILES['adsImage']['tmp_name']))
        {
            $prf = $this->imageBlobFile('adsImage');
            
            $uadImageWidth  = $prf['width'];
            $uadImageHeight = $prf['height'];
            $uadImageType   = $prf['type'];
            $uadImageBlob   = $prf['blob']; 

            $whereUad = array('userid'=>$user_id,'blobid'=>$userData['uadblobid']);
            $this->model->deleteQuery('qa_blobs',$whereUad);

            $profileData = array('blobid'=>$uadblobid,'format'=>$uadImageType,'content'=>$uadImageBlob,'userid'=>$user_id,'created'=>$created);
            $this->model->insertQuery1('qa_blobs',$profileData);
        }
        else
        {            
            $uadblobid       = $userData['uadblobid'];
            $uadImageWidth   = $userData['uadwidth'];
            $uadImageHeight  = $userData['uadheight'];
        }
        

        $avatarblobid = str_shuffle('1324567890561218519'); 
        if(!empty($_FILES['profileImage']['tmp_name']))
        {
            $prf = $this->imageBlobFile('profileImage');
            
            $profileImageWidth  = $prf['width'];
            $profileImageHeight = $prf['height'];
            $profileImageType   = $prf['type'];
            $profileImageBlob   = $prf['blob']; 

            $whereBlob = array('userid'=>$user_id,'blobid'=>$userData['avatarblobid']);
            $this->model->deleteQuery('qa_blobs',$whereBlob);

            $profileData = array('blobid'=>$avatarblobid,'format'=>$profileImageType,'content'=>$profileImageBlob,'userid'=>$user_id,'created'=>$created);
            $this->model->insertQuery1('qa_blobs',$profileData);
        }
        else
        {            
            $avatarblobid       = $userData['avatarblobid'];
            $profileImageWidth  = $userData['avatarwidth'];
            $profileImageHeight = $userData['avatarheight'];
        }

        $coverblobid = str_shuffle('9024467810661218519'); 
        if(!empty($_FILES['coverImage']['tmp_name']))
        {
            $prf = $this->imageBlobFile('coverImage');
            
            $coverImageWidth  = $prf['width'];
            $coverImageHeight = $prf['height'];
            $coverImageType   = $prf['type'];
            $coverImageBlob   = $prf['blob']; 

            $whereBlob = array('userid'=>$user_id,'blobid'=>$userData['coverblobid']);
            $this->model->deleteQuery('qa_blobs',$whereBlob);

            $profileData = array('blobid'=>$coverblobid,'format'=>$coverImageType,'content'=>$coverImageBlob,'userid'=>$user_id,'created'=>$created);
            $this->model->insertQuery1('qa_blobs',$profileData);
        }
        else
        {            
            $coverblobid      = $userData['coverblobid'];
            $coverImageWidth  = $userData['coverwidth'];
            $coverImageHeight = $userData['coverheight'];
        }
        // end profile image
        
        $about_me = $this->input->post('about_me');
        $full_name = $this->input->post('full_name');
        $location = $this->input->post('location');
        $links_websites = $this->input->post('links_websites');
        $social_networks = $this->input->post('social_networks');  

        $whereCount = array('userid'=>$user_id);         
        if(($this->model->countQuery('qa_userprofile',$whereCount))>0){                        
            $this->profileDataUpdate($user_id,$links_websites,$social_networks,$full_name,$location,$about_me);
                                     
        }else{
            $this->profileDataInsert($user_id,$links_websites,$social_networks,$full_name,$location,$about_me);
        }         
        
        $data = array(
            'handle'=> $username,
            'email' => $email,
            'paypal'=> $paypal,
            'uadimageurl' => (!empty($uadimageurl))?$uadimageurl:$userData['uadimageurl'],
            'avatarblobid'=> $avatarblobid,
            'avatarwidth' => $profileImageWidth,
            'avatarheight'=> $profileImageHeight,
            'coverblobid' => $coverblobid,
            'coverwidth'  => $coverImageWidth,
            'coverheight' => $coverImageHeight,
            'uadblobid'   => $uadblobid,
            'uadwidth'    => $uadImageWidth,
            'uadheight'   => $uadImageHeight,
            'flags'       => $private_messages.$publications_wall
        );        
        
        $updateProfile = $this->model->updateQuery("qa_users",$data,$whereUserid);
        $resp = array('code'=>'200','message'=>'Perfil actualizado con exito.');   
        $this->response($resp);   
    }

     
    public function getProfile_post()
    {
        $userid = $this->input->post('userid');
        $login_userid = $this->input->post('login_userid');
        $data = array('userid' => $userid,'login_userid' => $login_userid);
        $required_parameter = array('userid','login_userid');
        $chk_error = $this->check_required_value($required_parameter, $data);
        if ($chk_error){
            $resp = array('code'=>'501','message'=>'Missing ' .strtoupper($chk_error['param']));
            @$this->response($resp);
        }

        $wherePrf  = array('userid'=>$userid);
        $select = 'title,content';
        $userPrf = $this->model->fetchQuery($select,'qa_userprofile',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$wherePrf,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);

        $userData = $this->model->getUserProfile($userid);
        $qupvotes = $userData['qupvotes'];
        $aupvotes = $userData['aupvotes'];
        $positiveVotes = $qupvotes+$aupvotes;
                
        date_default_timezone_set('Asia/Kolkata');
        $created=$this->agoTimeSet(strtotime($userData['created']));

        $wherePost = array('userid'=>$userid,'type'=>'Q');
        $totalPost = $this->model->countQuery('qa_posts',$wherePost);
        
        $wherePostReply = "userid=$userid AND (type='A' OR type='C')";
        $totalReply = $this->model->countQuery('qa_posts',$wherePostReply);

        $whereFollowing = "userid=$userid AND entitytype='U' AND nouserevents='0' AND entityid!=''";
        $totalFollowing = $this->model->countQuery('qa_userfavorites',$whereFollowing);

        $whereFollowers = "entityid=$userid AND entitytype='U' AND nouserevents='0' AND userid!=''";
        $totalFollowers = $this->model->countQuery('qa_userfavorites',$whereFollowers);

        $whereSum = "seller=$userid";
        $totalSum = $this->model->sumQuery('qa_adimvipre','price',$whereSum);

        $whereFollow = array('userid'=>$login_userid,'entityid'=>$userid,'entitytype'=>'U','nouserevents'=>0);
        if(($this->model->countQuery('qa_userfavorites',$whereFollow))>0)
            $user_followup ='1';
        else
            $user_followup ='0';

        $credit=$userData['credit'];
        $record = array(
            'userid'        =>$userData['userid'],
            'username'      =>$userData['handle'],
            'email'         =>$userData['email'],
            'paypal'        =>(!empty($userData['paypal']))?$userData['paypal']:'',
            'avatarblobid'  =>(!empty($userData['avatarblobid']))?$userData['avatarblobid']:'',
            'coverblobid'   =>(!empty($userData['coverblobid']))?$userData['coverblobid']:'',
            'uadblobid'     =>(!empty($userData['uadblobid']))?$userData['uadblobid']:'',
            'uadimageurl'   =>(!empty($userData['uadimageurl']))?$userData['uadimageurl']:'',
            'wallposts'     =>($userData['wallposts']>0)?$userData['wallposts']:'0',
            'points'        =>(($userData['points'])>0)?$userData['points'].' Puntos':'0 Punto',
            'created'       =>$created,
            'about'         =>(!empty($userPrf[0]['content']))?$userPrf[0]['content']:'',
            'location'      =>(!empty($userPrf[1]['content']))?$userPrf[1]['content']:'',
            'name'          =>(!empty($userPrf[2]['content']))?$userPrf[2]['content']:'',
            'social-networks'=>(!empty($userPrf[3]['content']))?$userPrf[3]['content']:'',
            'website'       =>(!empty($userPrf[4]['content']))?$userPrf[4]['content']:'',
            'totalPost'     =>($totalPost>0)?"$totalPost":'0',
            'totalReply'    =>($totalReply>0)?"$totalReply":'0',
            'totalFollowing'=>($totalFollowing>0)?"$totalFollowing":'0',
            'totalFollowers'=>($totalFollowers>0)?"$totalFollowers":'0',
            'positiveVotes' =>($positiveVotes>0)?"$positiveVotes":'0',
            'credit'        =>($credit>0)?'$ '."$credit":'$ 0',
            'accumulated_money'=>'$ '.$totalSum,
            'followup'=>$user_followup
        );
        
        if(empty($userData)){
            $resp = array('code'=>'400','message'=>'Failure','response'=>array('message'=>'ID de usuario invalido.'));        
        }else{
            $resp = array('code'=>'200','message'=>'SUCCESS','response'=>array($record));
        }
        @$this->response($resp);
    }

    public function userBalance_post()
    {
        $userid = $this->input->post('userid');
        $data = array('userid' => $userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter, $data);
        if ($chk_error){
            $resp = array('code'=>'501','message'=>'Missing ' .strtoupper($chk_error['param']));
            @$this->response($resp);
        }

        $wherePrf  = array('userid'=>$userid);
        $select = 'userid,credit';
        $userData = current($this->model->fetchQuery($select,'qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$wherePrf,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL));
        $credit = $userData['credit'];
        
        $whereSum = "seller=$userid";
        $totalSum = $this->model->sumQuery('qa_adimvipre','price',$whereSum);

        $credit=$userData['credit'];
        $record = array(
            'userid'        =>$userData['userid'],            
            'credit'        =>($credit>0)?'$ '."$credit":'$ 0',
            'accumulated_money'=>'$ '.$totalSum
        );
        if(empty($userData)){
            $resp = array('code'=>'400','message'=>'Failure','message'=>'ID de usuario invalido.');        
        }else{
            $resp = array('code'=>'200','message'=>'success','balance'=>array($record));
        }
        @$this->response($resp);
    }

    public function changePassword_post()
    {
        $user_id = $this->input->post('user_id');
        $oldPass = $this->input->post('oldPass');
        $newPass = $this->input->post('newPass');
        $confirmPass = $this->input->post('confirmPass');        
        
        $checkData = array(
            'user_id' => $user_id,
            'old_password' => $oldPass,
            'new_password' => $newPass,
            'confirm_password' => $confirmPass
        );
        $required_parameter = array('user_id','old_password','new_password','confirm_password');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        if($newPass!=$confirmPass){
            $resp = array('code' => '404', 'message' => 'Confirm password is wrong');
            @$this->response($resp); exit;
        } 

        $where = array('userid'=>$user_id);
        $data = $this->model->getsingle('qa_users', $where,'userid,email,handle,wallposts,passsalt,HEX(passcheck) AS passcheck,createip,created');
        $salt= $data->passsalt;
        $oldPassword = strtolower(sha1(substr($salt, 0, 8).$oldPass.substr($salt, 8)));
        
        if($oldPassword == strtolower($data->passcheck))
        {
            $string = '';    
            $salt = isset($confirmPass) ? $this->qa_random_alphanum(16) : null;
            $password = sha1(substr($salt, 0, 8).$confirmPass.substr($salt, 8));
            $newPassword = hex2bin($password);
            $where = array('userid'=>$user_id);
            $data = array(
                'passsalt'=>$salt,
                'passcheck'=>$newPassword
            );
            $this->model->updateQuery('qa_users',$data,$where);
            $resp = array('code' => '200', 'message' => 'Cambiar contraseña exitosamente.');            
        }
        else{
            $resp = array('code' => '404', 'message' => 'La contraseña anterior es incorrecta.'); 
        
        }

        @$this->response($resp);
    }

    public function getPostDetail_post()
    {
        $site_url = base_url();
        $postid = $this->input->post('postid');
        $login_userid = $this->input->post('userid');
        $checkData  = array('postid'=>$postid,'userid'=>$login_userid);
        $required_parameter = array('postid','userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp);
        }
        $whereReport = array('postid'=>$postid,'userid'=>$login_userid,'flag'=>1);
        
        $data= $this->model->postDataGet($postid);
        
        if(($data['userid'])>0)
        {
            $userid = $data['userid'];
            $andWhere = array('userid'=>$userid);
            $profileData = $this->model->fetchQuery('content','qa_userprofile',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);
            
            $full_name = (!empty($profileData))?$profileData[2]['content']:'';
            
        }else{
            $full_name='';
        }
          
        $post_date = date('d M',strtotime($data['post_date']));
        $post_time = date('i',strtotime($data['post_date'])).' min.';
         
        if(($data['netvotes']>=0)){
            if(($data['netvotes']==0)){
                $netVotes = $data['netvotes'];
            }else{
                $netVotes = '+' .$data['netvotes'];
            }
        }else{
            $netVotes = $data['netvotes'];
        }

        $whereFav = array('userid'=>$login_userid,'entityid'=>$postid,'entitytype'=>'Q');
        if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
            $post_favourite ='1';
        else
            $post_favourite ='0';

        $entityid = $data['userid'];
        $whereFollow = array('userid'=>$login_userid,'entityid'=>$entityid,'entitytype'=>'U','nouserevents'=>0);
        if(($this->model->countQuery('qa_userfavorites',$whereFollow))>0)
            $post_followup ='1';
        else
            $post_followup ='0';

        $whereMsg = array('parentid'=>$postid,'type'=>'A');
        if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
            $total_message =$post_msg;
        else
            $total_message ='0';

        $whereBy = array('buyer'=>$login_userid,'postid'=>$postid);
        if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
            $post_buy ='1';
        else
            $post_buy ='0';

        $whereUser = array('userid'=>$login_userid);
        $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
        $credit = '$'.$userCredit['credit'];

        $whereMeta = array('postid'=>$postid);
        $metaData = $this->model->fetchQuery('content','qa_postmetas',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereMeta,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);
        
        $meta_img = explode(" ",$metaData[0]['content']); 
        $extraImg = array();
        $countExtra =  count($meta_img);
        foreach($meta_img as $key=>$imgs){
            if(!empty($imgs)){
                if($imgs!=$data['post_image'])
                    $extraImg[] = array('url'.$key=>$imgs);
            }
        }
        
        $rplc = array("\n","\"","<p>","</p>");
        $post_description= str_replace($rplc,"",$metaData[1]['content']);
        
        $rLink = array(' ',"\n","\"");
        $post_titleSh = str_replace($rLink,"-",$data['post_title']);
        $share_link = 'https://www.adimvi.com/?qa='.$data['postid'].'/'.strtolower($post_titleSh);

        
        $report_status = $this->model->countQuery('qa_uservotes',$whereReport);

        $pathinfo = strtolower(pathinfo($data['post_image'], PATHINFO_EXTENSION));
        //echo $post_description; die();
        $record = array(
            'postid'=>$data['postid'],
            'userid'=>$data['userid'],
            'avatarblobid'=>($data['avatarblobid']!='')?$data['avatarblobid']:'',
            'categoryid'=>$data['categoryid'],
            'category_name'=>$data['category_name'],
            'netvotes'=>"$netVotes",
            'views'=>$data['views'],
            'post_title'=>str_replace($rplc,"",$data['post_title']),            
            'post_image'=>$data['post_image'],
            'post_extra_image'=>$extraImg,
            'post_date'=>$post_date,
            'post_time'=>$post_time,
            'report_status'=>($report_status>0)?'1':'0',
            'post_favourite'=>$post_favourite,
            'post_followup'=>$post_followup,
            'total_message'=>$total_message, 
            'tags'=>$data['tags'],
            'price'=>'$'.$data['price'],
            'pricer'=>($data['pricer']>0)?'1':'0',
            'credit'    => $credit,
            'post_buy'  =>$post_buy,
            'total_points'=>$data['points'].' Puntos',
            'username'=>$data['username'],
            'full_name'=>$full_name,
            'share_link'=>$share_link,
            'adimvi_promotions'=>($data['adimviad']>0)?'1':'0',
            'promotional_image'=>($data['userad']>0)?'1':'0',
            'notify'=>($data['notify']=='@')?'1':'0',
            'post_description'=>(!empty($post_description))?$post_description:'',
            "post_type"=> "publish",
            'file_type'=>($pathinfo=='png'||$pathinfo=='jpeg'||$pathinfo=='jpg')?'Image':'Video'
            
        );
        if(empty($record)){   
            $resp = array('code' => '404', 'message' => 'Post Data', 'response' => array('postinfo' => 'Registro de publicacion no encontrado.'));            
        }
        else {
            $resp = array('code' => '200', 'message' => 'SUCCESS', 'response' => array('postinfo'=>$record));            
        }
        $this->response($resp);

    }

    public function postByTagList_post()
    {
        $postid = $this->input->post('postid');
        $checkData  = array('postid'=>$postid);
        $required_parameter = array('postid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $select = 'postid,tags';
        $andWhere = "postid=$postid";
        $data = current($this->model->fetchQuery($select,'qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='postid',$ascDsc='DESC',$sLimit=NULL,$eLimit=NULL));

        if(!empty($data))
        {
            $tags = explode(',',$data['tags']);

            $holeData = array();
            foreach($tags as $key=>$val){
                //$tags_val = str_replace('#','',$val).'#';
                $record = array('tags'=>$val);
                $holeData[] = $record;
            }
            $resp = array('code'=>'200','message'=>'success','response'=>array('postid'=>$data['postid'],'tags'=>$holeData));            
        }
        else{
            $resp = array('code'=>'400','message'=>'Tags not found','response'=>array('tags'=>[]));
        }
        @$this->response($resp); exit;
    }

    public function getPostListByTags_post()
    {
        $site_url = base_url();
        $site_url = "https://adimvi.com/?qa=image&qa_blobid=";

        $tags = $this->input->post('tags');
        $login_userid = $this->input->post('userid');
        $limit = $this->input->post('limit');
        $offset = $this->input->post('offset');

        $checkData  = array('userid'=>$login_userid,'tags'=>$tags,'limit'=>$limit,'offset'=>$offset);
        $required_parameter = array('userid','tags','limit','offset');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $allposts = (Array)$this->model->getPostByTags($tags,$limit,$offset);
        foreach ($allposts as $value) 
        {
            $userid = $value['userid'];
            $post_date = date('d M',strtotime($value['post_date']));
            $post_time = date('i',strtotime($value['post_date'])).' min.';
         
            if(($value['netvotes']>=0)){
                if(($value['netvotes']==0)){
                    $netVotes = $value['netvotes'];
                }else{
                    $netVotes = '+' .$value['netvotes'];
                }
            }else{
                $netVotes = $value['netvotes'];
            }

            $whereFav = array('userid'=>$userid,'entityid'=>$value['postid'],'entitytype'=>'Q');
            if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
                $post_favourite ='1';
            else
                $post_favourite ='0';

            $whereFav = array('userid'=>$userid,'entityid'=>$value['postid'],'entitytype'=>'U');
            if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
                $post_followup ='1';
            else
                $post_followup ='0';

            $whereMsg = array('parentid'=>$value['postid'],'type'=>'A');
            if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
                $total_message =$post_msg;
            else
                $total_message ='0';

            $whereBy = array('buyer'=>$login_userid,'postid'=>$value['postid']);
            if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                $post_buy ='1';
            else
                $post_buy ='0';

            $whereUser = array('userid'=>$login_userid);
            $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
            $credit = '$'.$userCredit['credit'];
            
            $whereMeta = array('postid'=>$value['postid']);
            $metaData = $this->model->fetchQuery('title,content','qa_postmetas',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereMeta,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);

            // $meta_img = explode(" ",$metaData[0]['content']); 
            // $extraImg = array();
            // $countExtra =  count($meta_img);
            // foreach($meta_img as $key=>$imgs){
            //     if(!empty($imgs))
            //         $extraImg[] = array('url'.$key=>$imgs);
            // }

            $rplc = array("\n","\"","<p>","</p>");
            $post_description= str_replace($rplc,"",$metaData[1]['content']);

            $rLink = array(' ',"\n","\"");
            $post_titleSh = str_replace($rLink,"-",$value['post_title']);
            $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

            $result[] = array(
                'postid'=>$value['postid'],
                'userid'=>$value['userid'],
                'categoryid'=>$value['categoryid'],
                'category_name'=>$value['category_name'],
                'netvotes'=>"$netVotes",
                'views'=>$value['views'],
                'post_title'=>$value['post_title'],
                'post_description'=>(!empty($post_description))?$post_description:'',
                'post_image'=>$value['post_image'],
                'post_date'=>$post_date,
                'post_time'=>$post_time,
                'post_favourite'=>$post_favourite,
                'post_followup'=>$post_followup,
                'total_message'=>$total_message, 
                'tags'=>$value['tags'],
                'price'=>'$'.$value['price'],
                'pricer'=>($value['pricer']>0)?'1':'0', 
                'credit'    => $credit,
                'post_buy'  =>$post_buy,               
                'username'=>$value['handle'],
                'avatarblobid'=>($value['avatarblobid']!='')?$value['avatarblobid']:'',
                'share_link'=>$share_link
                
            );
        }
        if(empty($result)){
            $resp = array('code' => '404', 'message' => 'Empty Posts', 'response' => array('sales' => 'Data Not Found..'));
            $this->response($resp); 
        }
        else{
            $resp = array('code' => '200', 'message' => 'Publicaciones de categoría particular.', 'response' => array('posts'=>$result)); 
            $this->response($resp);
        }

    }

    public function relatedPostByCategory_post()
    {
        $site_url = base_url();
        $site_url = "https://adimvi.com/?qa=image&qa_blobid=";

        $login_userid = $this->input->post('userid');
        $postid = $this->input->post('postid');
        $categoryid = $this->input->post('categoryid');
    
        $checkData  = array('userid'=>$login_userid,'postid'=>$postid,'categoryid'=>$categoryid);
        $required_parameter = array('userid','postid','categoryid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $allposts = $this->model->getRelatedPost($postid,$categoryid);
        
        foreach ($allposts as $value) 
        {
            $post_date = date('d M',strtotime($value['post_date']));
            $post_time = date('i',strtotime($value['post_date'])).' min.';
            $userid = $value['userid'];
         
            if(($value['netvotes']>=0)){
                if(($value['netvotes']==0)){
                    $netVotes = $value['netvotes'];
                }else{
                    $netVotes = '+' .$value['netvotes'];
                }
            }else{
                $netVotes = $value['netvotes'];
            }

            // $whereFav = array('userid'=>$userid,'entityid'=>$value['postid'],'entitytype'=>'Q');
            // if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
            //     $post_favourite ='1';
            // else
            //     $post_favourite ='0';

            // $whereFav = array('userid'=>$userid,'entityid'=>$value['postid'],'entitytype'=>'U');
            // if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
            //     $post_followup ='1';
            // else
            //     $post_followup ='0';

            $whereMsg = array('parentid'=>$value['postid'],'type'=>'A');
            if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
                $total_message =$post_msg;
            else
                $total_message ='0';

            $whereBy = array('buyer'=>$login_userid,'postid'=>$value['postid']);
            if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                $post_buy ='1';
            else
                $post_buy ='0';

            $whereUser = array('userid'=>$login_userid);
            $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
            $credit = '$'.$userCredit['credit'];


            $whereMeta = array('postid'=>$value['postid']);
            $metaData = $this->model->fetchQuery('title,content','qa_postmetas',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereMeta,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);

            // $meta_img = explode(" ",$metaData[0]['content']); 
            // $extraImg = array();
            // $countExtra =  count($meta_img);
            // foreach($meta_img as $key=>$imgs){
            //     if(!empty($imgs))
            //         $extraImg[] = array('url'.$key=>$imgs);
            // }
            // print_r($extraImg); die();
            $rplc = array("\n","\"","<p>","</p>");
            $post_description= str_replace($rplc,"",$metaData[1]['content']);

            $price = ($value['price']>0)?$value['price']:'0';

            $rLink = array(' ',"\n","\"");
            $post_titleSh = str_replace($rLink,"-",$value['post_title']);
            $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

            $result[] = array(
                'postid'=>$value['postid'],
                'userid'=>$value['userid'],
                'categoryid'=>$value['categoryid'],
                'category_name'=>$value['category_name'],
                'netvotes'=>"$netVotes",
                //'views'=>$value['views'],
                'post_title'=>$value['post_title'],
                'post_description'=>(!empty($post_description))?$post_description:'',
                'post_image'=>$value['post_image'],
                'post_date'=>$post_date,
                'post_time'=>$post_time,
                //'post_favourite'=>$post_favourite,
                //'post_followup'=>$post_followup,
                'total_message'=>$total_message, 
                //'tags'=>$value['tags'],
                //'price'=>'$'.$price,
                'username'=>$value['handle'],
                'avatarblobid'=>($value['avatarblobid']!='')?$value['avatarblobid']:'',
                'price'     =>'$'.$value['price'],
                'pricer'    =>($value['pricer']>0)?'1':'0',
                'credit'    => $credit,
                'post_buy'  =>$post_buy,
                'share_link'=>$share_link
                
            );
        }
        if(empty($result)){
            $resp = array('code'=>'404','message'=>'No se encontro la publicacion relacionada con la categoría.','response' => array('posts'=>[]));
            $this->response($resp); 
        }
        else{
            $resp = array('code' => '200', 'message' => 'Category related post', 'response' => array('posts'=>$result)); 
            $this->response($resp);
        }

    }

    public function userPointupVote($userid,$andWhere,$check,$postComment)
    {
        if($check==1 || $check==2)
        {
            $data = current($this->model->fetchQuery('qupvotes,aupvotes','qa_userpoints',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL));            

            if($postComment=='postComment')
            {
                if($check==1){
                    $aupvotes = $data['aupvotes']-1;
                }
                else if($check==2){
                    $aupvotes = $data['aupvotes']+1;    
                }
                $updateData = array('aupvotes'=>$aupvotes); 
            }
            else
            {
                if($check==1){
                    $qupvotes = $data['qupvotes']-1;
                }
                else if($check==2){
                    $qupvotes = $data['qupvotes']+1;    
                }
                $updateData = array('qupvotes'=>$qupvotes);
            }
            
            return $this->model->updateQuery('qa_userpoints',$updateData,$andWhere);
        }
        else
        {
            $insertData = array('userid'=>$userid,'qupvotes'=>1);
            return $this->model->insertQuery('qa_userpoints',$insertData);
        }
    }    
   
    public function likeVote($userid,$postid,$like_dislike_type,$postComment)
    {
        $andWhere = array('postid'=>$postid);
        $data = current($this->model->fetchQuery('upvotes,downvotes','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL));
        //print_r($data); die();
        $upvotes = $data['upvotes'];
        $downvotes = $data['downvotes'];        

        $where = array('userid'=>$userid,'postid'=>$postid);
        if(($this->model->countQuery('qa_uservotes',$where))>0)
        {
            $where = array('userid'=>$userid,'postid'=>$postid,'vote'=>1);
            if(($this->model->countQuery('qa_uservotes',$where))>0)
            {
               $vote = $upvotes-1;
               $where = array('userid'=>$userid,'postid'=>$postid);
               $updateData = array('vote'=>0);
               $this->model->updateQuery('qa_uservotes',$updateData,$where);
               
               $netvotes = $vote-$downvotes;
               $updateData = array('upvotes'=>$vote,'netvotes'=>$netvotes);
               $this->model->updateQuery('qa_posts',$updateData,$andWhere);

               $wherePoints = array('userid'=>$userid);
               $this->userPointupVote($userid,$wherePoints,1,$postComment);
               
               $removeLike="0";
               $msg = 'Vota como eliminado.'; 
            }
            else
            {
               $vote = $upvotes+1;
               $where = array('userid'=>$userid,'postid'=>$postid);
               $updateData = array('vote'=>1);
               $this->model->updateQuery('qa_uservotes',$updateData,$where);
               
               $netvotes = $vote-$downvotes;
               $updateData = array('upvotes'=>$vote,'netvotes'=>$netvotes);
               $this->model->updateQuery('qa_posts',$updateData,$andWhere);

               $wherePoints = array('userid'=>$userid);
               $this->userPointupVote($userid,$wherePoints,2,$postComment);
               
               $removeLike="1";
               $msg = 'El voto le gusto con exito.';

                $params = "postid=$postid userid=$userid vote=1 oldvote=0"; 
                $insert = array(
                    'userid'=>$userid,
                    'handle'=>'username',
                    'event'=>'q_vote_up',
                    'params'=>$params,
                    'ipaddress'=>'186.108.226.48',
                    'datetime'=>date('Y-m-d H:i:s')
                );
                $this->model->insertQuery1('qa_eventlog',$insert);
            }          
        }
        else
        {
            $vote = $upvotes+1;
            $wherePoints = array('userid'=>$userid);
            if(($this->model->countQuery('qa_userpoints',$wherePoints))>0){
                $this->userPointupVote($userid,$wherePoints,2,$postComment);
            }else{
                $this->userPointupVote($userid,$wherePoints,0,$postComment);
            }

            $inserData = array('userid'=>$userid,'postid'=>$postid,'vote'=>1,'flag'=>0);
            $this->model->insertQuery('qa_uservotes',$inserData);
           
            $netvotes = $vote-$downvotes;
            $updateData = array('upvotes'=>$vote,'netvotes'=>$netvotes);
            $this->model->updateQuery('qa_posts',$updateData,$andWhere);
            $removeLike="1";
            $msg = 'El voto le gusto con exito.';
            $netvotes = $vote-$downvotes;

            $params = "postid=$postid userid=$userid vote=1 oldvote=0"; 
            $insert = array(
                'userid'=>$userid,
                'handle'=>'username',
                'event'=>'q_vote_up',
                'params'=>$params,
                'ipaddress'=>'186.108.226.48',
                'datetime'=>date('Y-m-d H:i:s')
            );
            $this->model->insertQuery1('qa_eventlog',$insert);
        }
        

        if(($netvotes>=0)){
            if(($netvotes==0)){
                $netvotes = $netvotes;
            }else{
                $netvotes = '+' .$netvotes;
            }
        }else{
            $netvotes = $netvotes;
        }

        $record = array(
            //'upvotes'=>"$vote",
            'netvotes'=>"$netvotes",
            'userid'=>$userid,
            'postid'=>$postid,
            'like_dislike_type'=>$like_dislike_type,
            'likeType'=>$removeLike                   
        );

        $resp = array('code'=>'200','message'=>$msg,'response'=>array('postVotes'=>$record));               
        $this->response($resp);
    }

    public function userPointdownVote($userid,$andWhere,$check,$postComment)
    {
        if($check==1 || $check==2)
        {
            $data = current($this->model->fetchQuery('qdownvotes,adownvotes','qa_userpoints',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL));
            if($postComment=='postComment')
            {                
                if($check==1){
                    $adownvotes = $data['adownvotes']-1;
                }
                else if($check==2){
                    $adownvotes = $data['adownvotes']+1;    
                }
                $updateData = array('adownvotes'=>$adownvotes);
            }
            else
            {
                if($check==1){
                    $qdownvotes = $data['qdownvotes']-1;
                }
                else if($check==2){
                    $qdownvotes = $data['qdownvotes']+1;    
                }
                $updateData = array('qdownvotes'=>$qdownvotes);
            }
            return $this->model->updateQuery('qa_userpoints',$updateData,$andWhere);
        }
        else
        {
            $insertData = array('userid'=>$userid,'qdownvotes'=>1);
            return $this->model->insertQuery('qa_userpoints',$insertData);
        }
    }

    public function disLikeVote($userid,$postid,$like_dislike_type,$postComment)
    {
        $andWhere = array('postid'=>$postid);
        $data = current($this->model->fetchQuery('upvotes,downvotes','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL));
        //print_r($data);
        $upvotes = $data['upvotes'];
        $downvotes = $data['downvotes'];        

        $where = array('userid'=>$userid,'postid'=>$postid);
        if(($this->model->countQuery('qa_uservotes',$where))>0)
        {
            $where = array('userid'=>$userid,'postid'=>$postid,'vote'=>'-1');
            if(($this->model->countQuery('qa_uservotes',$where))>0)
            {
               $vote = $downvotes-1;
               $where = array('userid'=>$userid,'postid'=>$postid);
               $updateData = array('vote'=>0);
               $this->model->updateQuery('qa_uservotes',$updateData,$where);
               
               $netvotes = $upvotes-$vote;
               $updateData = array('downvotes'=>$vote,'netvotes'=>$netvotes);
               $this->model->updateQuery('qa_posts',$updateData,$andWhere);

               $wherePoints = array('userid'=>$userid);
               $this->userPointdownVote($userid,$wherePoints,1,$postComment);
               
               $removeLike="0";
               $msg = 'No me gusta el voto eliminado.'; 
            }
            else
            {
               $vote = $downvotes+1;
               $where = array('userid'=>$userid,'postid'=>$postid);
               $updateData = array('vote'=>-1);
               $this->model->updateQuery('qa_uservotes',$updateData,$where);
               
               $netvotes = $upvotes-$vote;
               $updateData = array('downvotes'=>$vote,'netvotes'=>$netvotes);
               $this->model->updateQuery('qa_posts',$updateData,$andWhere);

               $wherePoints = array('userid'=>$userid);
               $this->userPointdownVote($userid,$wherePoints,2,$postComment);
               
               $removeLike="1";
               $msg = 'Voto rechazado con exito.';

                $params = "postid=$postid userid=$userid vote=1 oldvote=0"; 
                $insert = array(
                    'userid'=>$userid,
                    'handle'=>'username',
                    'event'=>'q_vote_down',
                    'params'=>$params,
                    'ipaddress'=>'186.108.226.48',
                    'datetime'=>date('Y-m-d H:i:s')
                );
                $this->model->insertQuery1('qa_eventlog',$insert);
            }          
        }
        else
        {
            $vote = $downvotes+1;
            $wherePoints = array('userid'=>$userid);
            if(($this->model->countQuery('qa_userpoints',$wherePoints))>0){
                $this->userPointdownVote($userid,$wherePoints,2,$postComment);
            }else{
                $this->userPointdownVote($userid,$wherePoints,0,$postComment);
            }

            $inserData = array('userid'=>$userid,'postid'=>$postid,'vote'=>-1,'flag'=>0);
            $this->model->insertQuery('qa_uservotes',$inserData);
           
            $netvotes = $upvotes-$vote;
            $updateData = array('downvotes'=>$vote,'netvotes'=>$netvotes);
            $this->model->updateQuery('qa_posts',$updateData,$andWhere);
            $removeLike="1";
            $msg = 'Voto rechazado con exito'; 

            $params = "postid=$postid userid=$userid vote=1 oldvote=0"; 
            $insert = array(
                'userid'=>$userid,
                'handle'=>'username',
                'event'=>'q_vote_down',
                'params'=>$params,
                'ipaddress'=>'186.108.226.48',
                'datetime'=>date('Y-m-d H:i:s')
            );
            $this->model->insertQuery1('qa_eventlog',$insert);           
        }

        if(($netvotes>=0)){
            if(($netvotes==0)){
                $netvotes = $netvotes;
            }else{
                $netvotes = '+' .$netvotes;
            }
        }else{
            $netvotes; //= '-' .$netvotes;
        }
        
        $record = array(
            
            'netvotes'=>"$netvotes",
            'userid'=>$userid,
            'postid'=>$postid,
            'like_dislike_type'=>$like_dislike_type,
            'likeType'=>$removeLike                   
        );

        $resp = array('code'=>'200','message'=>$msg,'response'=>array('postVotes'=>$record));               
        $this->response($resp);
    }

    public function setVote_post()
    {
        $userid = $this->input->post('userid');
        $postid = $this->input->post('postid');
        $like_dislike_type = $this->input->post('like_dislike_type');
        $postComment = (empty($this->input->post('postComment')))?'':'postComment';

        $checkData  = array('userid'=>$userid,'postid'=>$postid,'like_dislike_type'=>$like_dislike_type);
        $required_parameter = array('userid','postid','like_dislike_type');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'400','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }
        
        if($like_dislike_type==1){
            $this->likeVote($userid,$postid,$like_dislike_type,$postComment);
        }
        else if($like_dislike_type==0){
            $this->disLikeVote($userid,$postid,$like_dislike_type,$postComment);
        }
        else if($like_dislike_type!=1 || $like_dislike_type!=0){
            $resp = array('code' => '400', 'message' =>'Invalid vote type');
            @$this->response($resp); 
        }
    }

    public function setPostReport_post()
    {
        $userid = $this->input->post('userid');
        $postid = $this->input->post('postid');
        
        $where = array('postid'=>$postid,'userid'=>$userid);
        $this->model->countQuery('qa_uservotes',$where);
        if(($this->model->countQuery('qa_uservotes',$where))>0)
        {
            $where1 = array('postid'=>$postid,'userid'=>$userid,'flag'=>1);
            if($this->model->countQuery('qa_uservotes',$where1)>0)
            {
                $updateData = array('flag'=>0);
                $this->model->updateQuery('qa_uservotes',$updateData,$where1);
                $resp = array('status'=>400,'code'=>'success','message'=>'Report removed','report_status'=>'0');
            }else{
                $updateData = array('flag'=>1);
                $this->model->updateQuery('qa_uservotes',$updateData,$where);
                $resp = array('status'=>200,'code'=>'success','message'=>'Report added','report_status'=>'1');
            }
        }else{
            $inserData = array('userid'=>$userid,'postid'=>$postid,'vote'=>0,'flag'=>1);
            $this->model->insertQuery('qa_uservotes',$inserData);
            $resp = array('status'=>200,'code'=>'success','message'=>'Report added','report_status'=>'1');
        }
        $this->response($resp);
                       
        //$this->model->updateQuery('qa_posts',$updateData,$andWhere);
    }

    public function setUserFollowing_post()
    {
        $userid = $this->input->post('userid');
        $entityid = $this->input->post('entityid');
        
        $checkData = array('userid'=>$userid,'entityid'=>$entityid);
        $required_parameter = array('userid','entityid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $whereFoll = array('userid'=>$userid,'entityid'=>$entityid,'entitytype'=>'U','nouserevents'=>0);
        if(($this->model->countQuery('qa_userfavorites',$whereFoll))>0){
            $this->model->deleteQuery('qa_userfavorites',$whereFoll);
            $resp = array('code'=>'200','status'=>'0','message' =>'Seguimiento eliminado con exito.');
            $this->response($resp); exit;
        }
        
        $data = array(
           'userid'=>$userid,
           'entityid'=>$entityid,
           'entitytype'=>'U',
           'nouserevents'=>'0'        
        );
        $this->model->insertQuery('qa_userfavorites',$data);
        $resp = array('code'=>'200','status'=>'1','message' =>'Seguimiento agregado con exito.','response'=>array('addFollowup'=>$data));         
        $this->response($resp);
    }

    public function getUserFollowing_post()
    {
        $userid = $this->input->post('userid');
        //$entitytype = $this->input->post('entitytype');
        $checkData = array('userid' => $userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $where = array('userid'=> $userid);
        $data = $this->model->getUserFollowing($userid);

        $holeData = array();
        foreach($data as $key=>$row)
        {
            if(!empty($row['avatarblobid'])){
                $avatarblobid = $row['avatarblobid'];
            }else{
                $avatarblobid = '';
            }

            if(!empty($row['userid']))
            {
                $record = array(
                    "avatarblobid"=>$avatarblobid,
                    "userid"=>$row['userid'],                
                    "username"=>!empty($row['username'])?$row['username']:'',
                    'total_points'=>!empty($row['points'])?$row['points']:''                
                );
                $holeData[] = $record;
            }            
        }
       
        if(empty($data)){
            $resp = array('code' => '400', 'message' => 'Failure', 'response' => array('message' => 'No Following Users'));
            @$this->response($resp);   
        }
        else {
            $resp = array('code' => '200', 'message' => 'SUCCESS', 'response' => array('userFollowing' => $holeData));   
            $this->response($resp); 
        }
    }

    public function getPostFollowing_post()
    {
        $site_url = base_url();
        $login_userid = $this->input->post('userid');
        $startLimit = $this->input->post('limit');
        $endLimit = $this->input->post('offset');

        $checkData = array('userid' => $login_userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $dataF = $this->model->getPostFollowingUserID($login_userid); 
        $holeData = array();
        foreach($dataF as $rowF)
        {
            $db_userid = $rowF['userid'];
            $holePosts = $this->model->getPostFollowing($db_userid,$startLimit,$endLimit);
                        
            foreach($holePosts as $row)
            {                
               if(($row['netvotes']>=0)){
                    if(($row['netvotes']==0)){
                        $netvotes = $row['netvotes'];
                    }else{
                        $netvotes = '+' .$row['netvotes'];
                    }
                }else{
                    $netvotes = $row['netvotes'];
                }   

                if(($row['userid'])>0)
                {
                    $userid = $row['userid'];
                    $andWhere = array('userid'=>$userid);
                    $profileData = $this->model->fetchQuery('content','qa_userprofile',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);
                    
                    $full_name = (!empty($profileData))?$profileData[2]['content']:'';
                    
                }else{
                    $full_name='';
                }

                $post_date = date('d M',strtotime($row['post_date']));
                $post_time = date('i',strtotime($row['post_date'])).' min.';
                if(!empty($profiles['avatarblobid'])){
                    $avatarblobid = $row['avatarblobid'];
                }else{
                    $avatarblobid = '';
                }

                $whereMsg = array('parentid'=>$row['postid'],'type'=>'A');
                if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
                    $total_message =$post_msg;
                else
                    $total_message ='0';

                $whereBy = array('buyer'=>$login_userid,'postid'=>$row['postid']);
                if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                    $post_buy ='1';
                else
                    $post_buy ='0';

                $whereUser = array('userid'=>$login_userid);
                $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
                $credit = '$'.$userCredit['credit'];


                $whereMeta = array('postid'=>$row['postid']);
                $metaData = $this->model->fetchQuery('title,content','qa_postmetas',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereMeta,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);
                
                $meta_img = explode(" ",$metaData[0]['content']); 
                $extraImg = array();
                $countExtra =  count($meta_img);
                foreach($meta_img as $key=>$imgs){
                    if(!empty($imgs))
                        $extraImg[] = array('url'.$key=>$imgs);
                }
                $rplc = array("\n","\"","<p>","</p>");
                $post_description= str_replace($rplc,"",$metaData[1]['content']);

                
                if(!empty($row['userid']) && !empty($row['postid']))
                {
                    $rLink = array(' ',"\n","\"","<p>","</p>");
                    $post_titleSh = str_replace($rLink,"-",$row['post_title']);
                    $share_link = 'https://www.adimvi.com/?qa='.$row['postid'].'/'.strtolower($post_titleSh);

                    $data = array(
                        'postid'=>$row['postid'],
                        'userid'=>$row['userid'],
                        'avatarblobid'=>($row['avatarblobid']!='')?$row['avatarblobid']:'',
                        'categoryid'=>$row['categoryid'],
                        'category_name'=>$row['cat_title'],
                        'netvotes'=>"$netvotes",
                        'views'=>$row['views'],
                        'post_title'=>$row['post_title'],
                        'post_description'=>(!empty($post_description))?$post_description:'',
                        'post_image'=>$row['post_image'],
                        'post_extra_image'=>$extraImg,
                        'post_date'=>$post_date,
                        'post_time'=>$post_time,
                        'total_message'=>$total_message,
                        'tags'=>$row['tags'],
                        'total_points'=>$row['points'].' Puntos',
                        'username'=>$row['username'],
                        'full_name'=>$full_name,
                        'share_link'=>$share_link,
                        'price'     =>'$'.$row['price'],
                        'pricer'    =>($row['pricer']>0)?'1':'0',
                        'credit'    => $credit,
                        'post_buy'  =>$post_buy
                    );

                    $holeData[] = $data;
                }     
            }
        }
               
        if(empty($data)){
            $resp = array('code' => '400', 'message' => 'No hay siguiente publicacion.');            
        }
        else {
            $resp = array('code' => '200', 'message' => 'SUCCESS', 'response' => array('postFollowing' => $holeData));
        }
        @$this->response($resp);
    }

    public function postComment_post()
    {
        $userid = $this->input->post('userid');
        $postid = $this->input->post('postid');
        $categoryid = $this->input->post('categoryid');
        $comment = $this->input->post('comment');
        $comment_type = $this->input->post('type');
        
        $checkData  = array('userid'=>$userid,'postid'=>$postid,'categoryid'=>$categoryid,'comment'=>$comment,'type'=>$comment_type);
        $required_parameter = array('userid','postid','categoryid','comment','type');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'400','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }
        
        date_default_timezone_set('Asia/Kolkata');
        $created = date("Y-m-d H:i:s");
        $data = array(
            'userid'=>$userid,
            'parentid'=>$postid,
            'categoryid'=>$categoryid,
            'catidpath1'=>$categoryid,
            'lastuserid'=>$userid,
            'created'=>$created,
            'content'=>$comment,
            'type'=>$comment_type          
        );

        $andWhere = "postid=$postid";
        $postRec = current($this->model->fetchQuery('userid','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere));
        $post_userid = $postRec['userid'];
        $dataPayload = array(
            'body'=>$comment,
            'title'=>'Comment on your post',
            'sub_text'=>'sub_text',
            'device_type'=>'ios'            
        );

        $returnArr = $this->push_notification($dataPayload,$post_userid);

        if(($this->model->insertQuery('qa_posts',$data))>0)
        {
            $resp = array('code'=>'200','message'=>'Comentario agregado exitosamente.');            
        }else{
            $resp = array('code'=>'400','message'=>'Comentario agregado fallado.');           
        }
        @$this->response($resp);
    }

    public function netVotesIcon($db_netvotes)
    {
        if($db_netvotes>=0)
        {
            if($db_netvotes==0)
                $netVotes = $db_netvotes;
            else
                $netVotes = '+' .$db_netvotes;            
        }else{
            $netVotes = $db_netvotes;
        }
        return $netVotes;
    }

    public function postCommentList_post()
    {                
        $postid = $this->input->post('postid');
        $userid = $this->input->post('userid');            

        $checkData  = array('postid'=>$postid,'userid'=>$userid);
        $required_parameter = array('postid','userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $rec = $this->model->postCommentList($postid,$userid);
        //print_r($rec); die(); 
        $commentData = array(); 
        foreach($rec as $data)
        { 
            $whereOwn = array('postid'=>$data['parentid']);
            $owner = current($this->model->fetchQuery('userid','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereOwn));
            $owner_userid = $owner['userid'];

            $commentsCount = $this->model->postCommentCount($data['postid'],$userid);
            $commentsCount = ($commentsCount>0)?"+$commentsCount":'0';        

            date_default_timezone_set('Asia/Kolkata');
            $created=$this->agoTimeSet(strtotime($data['post_date']));

            $netVotes = $this->netVotesIcon($data['netvotes']);
            $points = (($data['points'])>0)?$data['points'].' Puntos':'0 Punto';            
            $record = array(
                'postid'=>$data['postid'],
                'parentid'=>$data['parentid'], 
                'userid'=>$data['userid'], // comment owner id
                'owner_userid'=>$owner_userid, // post owner id
                'categoryid'=>$data['categoryid'],
                'commentsCount'=>"$commentsCount",
                'avatarblobid'=>($data['avatarblobid']!='')?$data['avatarblobid']:'',
                'netvotes'=>"$netVotes",
                'total_points'=>$points,
                'username'=>$data['username'],
                'comment'=>$data['comment'],               
                'created'=>$created,
                'comment_type'=>$data['type'],
                //'comment_comment'=>$allCommentComment                
            );

            $commentData[] = $record;
        }

        if((!empty($data))){            
            $resp = array('code'=>'200','message'=>'SUCCESS','response'=>array('postComment'=>$commentData));            
        }else{
            $resp = array('code'=>'400','message'=>'Datos no encontrados.','response'=>array('postComment'=>[]));
        }
        @$this->response($resp); exit;
    }

    public function CommentCommentList_post()
    {                
        $postid = $this->input->post('postid');
        $userid = $this->input->post('userid');            

        $checkData  = array('postid'=>$postid,'userid'=>$userid);
        $required_parameter = array('postid','userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $wherePar = array('postid'=>$postid,'type'=>'A');
        $owner = current($this->model->fetchQuery('parentid','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$wherePar));
        $parentid = $owner['parentid'];

        $whereOwn = array('postid'=>$parentid,'type'=>'Q');
        $owner = current($this->model->fetchQuery('userid','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereOwn));
        $post_owner_userid = $owner['userid'];

        if($post_owner_userid>0)
        {
            $ComentCmts = $this->model->postCommentComment($postid,$userid);
            $commentsCount =  count($ComentCmts);          
            $allCommentComment = array();
            foreach($ComentCmts as $ComentCmt)
            {                                
                date_default_timezone_set('Asia/Kolkata');
                $created=$this->agoTimeSet(strtotime($ComentCmt['post_date']));

                $netVotes = $this->netVotesIcon($ComentCmt['netvotes']);
                $points = (($ComentCmt['points'])>0)?$ComentCmt['points'].' Puntos':'0 Punto';
                
                $comment_record = array(
                    'postid'=>$ComentCmt['postid'],
                    'userid'=>$ComentCmt['userid'], //comment owner id
                    'parentid'=>$ComentCmt['parentid'],
                    'post_owner_userid'=>$post_owner_userid, //post owner id
                    'categoryid'=>$ComentCmt['categoryid'],
                    'avatarblobid'=>($ComentCmt['avatarblobid']!='')?$ComentCmt['avatarblobid']:'',
                    'total_points'=>$points,
                    'username'=>$ComentCmt['username'],
                    'comment'=>$ComentCmt['comment'],               
                    'created'=>$created,
                    'comment_type'=>$ComentCmt['type']
                    
                );
                $allCommentComment[] = $comment_record;            
            }
        }

        if((!empty($ComentCmts))){            
            $resp = array('code'=>'200','message'=>'SUCCESS','response'=>array('CommentComment'=>$allCommentComment));            
        }else{
            $resp = array('code'=>'400','message'=>'Datos no encontrados.','response'=>array('CommentComment'=>[]));
        }
        @$this->response($resp); exit;
    }

    public function commentDelete_post()
    {
        $postid = $this->input->post('postid');
        //$userid = $this->input->post('userid');
        
        $checkData  = array('postid'=>$postid);
        $required_parameter = array('postid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $where_parent = array('parentid'=>$postid);
        $where_post = array('postid'=>$postid);
        
        $this->model->deleteQuery('qa_posts',$where_parent);          
        $this->model->deleteQuery('qa_posts',$where_post);
        $resp = array('code'=>'200','message'=>'Comentario eliminado con exito.');            
        
        @$this->response($resp);
    }

    public function editHideShowpostComment_post()
    {
        $postid  = $this->input->post('postid');
        $type    = $this->input->post('type');
        $comment = (!empty($this->input->post('comment')))?$this->input->post('comment'):'comment_edit';
        
        $checkData  = array('postid'=>$postid,'comment'=>$comment,'type'=>$type);
        $required_parameter = array('postid','comment','type');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'400','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }
        
        $where = array('postid'=>$postid);
        $pData = current($this->model->fetchQuery('parentid','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$where,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL));
        $parentid = ($pData['parentid']>0)?$pData['parentid']:'';

        date_default_timezone_set('Asia/Kolkata');
        $updated = date("Y-m-d H:i:s");
        if($comment=='comment_edit')
        {
            $data = array(
                'type'=>$type,
                'updated'=>$updated           
            );
        }else{
            $data = array(
                'content'=>$comment,
                'type'=>$type,
                'updated'=>$updated           
            );
        }

        if(($this->model->updateQuery('qa_posts',$data,$where))>0)
            $resp = array('code'=>'200','message'=>'Comentario actualizado con exito.','type'=>$type,'comment'=>$comment,'commentUpdatedId'=>$postid,'parentid'=>$parentid);        
        else
            $resp = array('code'=>'400','message'=>'Error al actualizar el comentario.');        
        @$this->response($resp);
    }


    public function getUserPublications_post()
    {
        $site_url = base_url();
        $site_url = "https://adimvi.com/?qa=image&qa_blobid=";

        $userid = $this->input->post('userid');
        $limit = $this->input->post('limit');
        $offset = $this->input->post('offset');

        $checkData  = array('userid'=>$userid,'limit'=>$limit,'offset'=>$offset);
        $required_parameter = array('userid','limit','offset');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp);
        }
        $login_userid = $this->input->post('userid');

        $where = array('userid'=> $userid);
        $allposts = $this->model->getpublicationofuser($userid,$limit,$offset);
        foreach ($allposts as $value) 
        {
            $post_date = date('d M',strtotime($value['post_date']));
            $post_time = date('i',strtotime($value['post_date'])).' min.';
         
            if(($value['netvotes']>=0)){
                if(($value['netvotes']==0)){
                    $netVotes = $value['netvotes'];
                }else{
                    $netVotes = '+' .$value['netvotes'];
                }
            }else{
                $netVotes = $value['netvotes'];
            }

            $whereFav = array('userid'=>$userid,'entityid'=>$value['postid'],'entitytype'=>'Q');
            if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
                $post_favourite ='1';
            else
                $post_favourite ='0';

            $whereFav = array('userid'=>$userid,'entityid'=>$value['postid'],'entitytype'=>'U');
            if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
                $post_followup ='1';
            else
                $post_followup ='0';

            $whereMsg = array('parentid'=>$value['postid'],'type'=>'A');
            if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
                $total_message =$post_msg;
            else
                $total_message ='0';

            $whereBy = array('buyer'=>$login_userid,'postid'=>$value['postid']);
            if(($this->model->countQuery('qa_adimvipre',$whereBy))>0)
                $post_buy ='1';
            else
                $post_buy ='0';

            $whereUser = array('userid'=>$login_userid);
            $userCredit = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereUser,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='userid',$ascDsc='DESC',$sLimit=0,$eLimit=1));
            $credit = '$'.$userCredit['credit'];


            $whereMeta = array('postid'=>$value['postid']);
            $metaData = $this->model->fetchQuery('title,content','qa_postmetas',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereMeta,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);

            // $meta_img = explode(" ",$metaData[0]['content']); 
            // $extraImg = array();
            // $countExtra =  count($meta_img);
            // foreach($meta_img as $key=>$imgs){
            //     if(!empty($imgs))
            //         $extraImg[] = array('url'.$key=>$imgs);
            // }
            $rplc = array("\n","\"","<p>","</p>");
            if(!empty($metaData[1]))            
                $post_description= str_replace($rplc,"",$metaData[1]['content']);
            else
                $post_description="";

            $rLink = array(' ',"\n","\"","<p>","</p>");
            $post_titleSh = str_replace($rLink,"-",$value['post_title']);
            $share_link = 'https://www.adimvi.com/?qa='.$value['postid'].'/'.strtolower($post_titleSh);

            $result[] = array(
                'postid'=>$value['postid'],
                'userid'=>$value['userid'],
                'categoryid'=>$value['categoryid'],
                'category_name'=>$value['category_name'],
                'netvotes'=>"$netVotes",
                'views'=>$value['views'],
                'post_title'=>$value['post_title'],
                'post_description'=>(!empty($post_description))?$post_description:'',
                'post_image'=>$value['post_image'],
                'post_date'=>$post_date,
                'post_time'=>$post_time,
                'post_favourite'=>$post_favourite,
                'post_followup'=>$post_followup,
                'total_message'=>$total_message, 
                'tags'=>(!empty($value['tags']))?$value['tags']:'',
                'price'=>'$'.$value['price'],
                'pricer'=>($value['pricer']>0)?'1':'0',                
                'username'=>$value['handle'],
                'avatarblobid'=>($value['avatarblobid']!='')?$value['avatarblobid']:'',
                'share_link'=>$share_link,
                'post_buy'  =>$post_buy,
                'credit'    => $credit,
                'post_type'=>'publish'
                
            );
        }
        if(empty($result)){
            $resp = array('code' => '404', 'message' => 'Datos no encontrados.', 'response' => array('sales' => 'Data Not Found..'));
            $this->response($resp); 
        }
        else{
            $resp = array('code' => '200', 'message' => 'Publicaciones de categoría particular.', 'response' => array('posts'=>$result)); 
            $this->response($resp);
        }

    }

    public function getProfileRecentPost_post()
    {
        $site_url = base_url();
        $site_url = "https://adimvi.com/?qa=image&qa_blobid=";

        $userid = $this->input->post('userid');
        
        $checkData  = array('userid'=>$userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $Rposts = $this->model->profileRecentPost($userid);
        foreach ($Rposts as $value) 
        {
            if(($value['netvotes']>=0)){
                if(($value['netvotes']==0)){
                    $netVotes = $value['netvotes'];
                }else{
                    $netVotes = '+' .$value['netvotes'];
                }
            }else{
                $netVotes = $value['netvotes'];
            }

            $whereMsg = array('parentid'=>$value['postid'],'type'=>'A');
            if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
                $total_message =$post_msg;
            else
                $total_message ='0';

            $result[] = array(
                'postid'=>$value['postid'],
                'userid'=>$value['userid'],
                'netvotes'=>"$netVotes",
                'views'=>$value['views'],
                'post_title'=>$value['post_title'],
                'post_image'=>$value['post_image'],
                'total_message'=>$total_message, 
            );
        }
        if(empty($result)){
            $resp = array('code'=>'404','message'=>'Datos no encontrados.');
            $this->response($resp); 
        }
        else{
            $resp = array('code'=>'200','message'=>'Publicacion de perfil reciente.','response'=>array('posts'=>$result)); 
            $this->response($resp);
        }

    }

    public function getMemberList_post()
    {
        $site_url = base_url();
        $site_url = "https://adimvi.com/?qa=image&qa_blobid=";
        
        $login_userid  = $this->input->post('userid');
        $limit  = $this->input->post('limit');
        $offset    = $this->input->post('offset');

        $checkData  = array('userid'=>$login_userid,'limit'=>$limit,'offset'=>$offset);
        $required_parameter = array('userid','limit','offset');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'400','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $data = $this->model->memberList($where='',$limit,$offset);
        $holeData = array();
        foreach ($data as $value) 
        {
            $points = ($value['points']>0)?$value['points']:'0';
            $userid = $value['userid'];

            $whereFollowing = "userid=$userid AND entitytype='U' AND nouserevents='0' AND entityid!=''";
            $totalFollowing = $this->model->countQuery('qa_userfavorites',$whereFollowing);

            $whereFollowers = "entityid=$userid AND entitytype='U' AND nouserevents='0' AND userid!=''";
            $totalFollowers = $this->model->countQuery('qa_userfavorites',$whereFollowers);

            $myFollowing = "userid=$login_userid AND entitytype='U' AND nouserevents='0' AND entityid=$userid";
            $follow = (($this->model->countQuery('qa_userfavorites',$myFollowing))>0)?"1":"0";

            
            $select='parentid,postid,netvotes,title,content';
            $where = "userid=$userid";
            $postData = $this->model->fetchQuery($select,'qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$where,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=0,$eLimit=3);
            
            $pRecord = array();
            if(!empty($postData))
            {                
                foreach ($postData as $row) 
                {
                    $netvotes = $this->netVotesIcon($row['netvotes']);
                    $postid=$row['postid'];

                    $whereMsg = array('parentid'=>$postid,'type'=>'A');
                    if(($post_msg = $this->model->countQuery('qa_posts',$whereMsg))>0)
                        $total_message =$post_msg;
                    else
                        $total_message ='0';

                    $rplc = array("\n","\"","<p>","</p>");
                    $title= str_replace($rplc,"",$row['title']);

                    $rPost = array(
                        'postid'=>$row['postid'],
                        'title'=>$title,
                        'post_image'=>$row['content'],
                        'netvotes'=>$row['netvotes'],
                        'total_message'=>$total_message,
                    );
                    $pRecord[] = $rPost;
                }
            }

            $result = array(
                'userid'=>$value['userid'],
                'email'=>$value['email'],
                'username'=>$value['username'],
                'points'=>$points.' Puntos',
                'avatarblobid'=>(!empty($value['avatarblobid']))?$value['avatarblobid']:'',
                'totalFollowing'=>$totalFollowing,
                'totalFollowers'=>$totalFollowers,
                'follow'=>$follow,
                'postData'=>$pRecord
            );

            $holeData[] = $result;
        }
        if(empty($result)){
            $resp = array('code'=>'404','message'=>'Datos no encontrados.');
            $this->response($resp); 
        }
        else{
            $resp = array('code'=>'200','message'=>'Registro de miembro.','response'=>array('members'=>$holeData)); 
            $this->response($resp);
        }

    }

    public function memberSearch_post()
    {
        $search  = $this->input->post('search');
        $limit  = (($this->input->post('limit'))>0)?$this->input->post('limit'):'0';
        $offset  = (($this->input->post('offset'))>0)?$this->input->post('offset'):'5000';

        $checkData  = array('search'=>$search);
        $required_parameter = array('search');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'400','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }
        $where = "WHERE u.handle LIKE '%$search%'";
        $data = $this->model->memberList($where,$limit,$offset);
        $holeData = array();
        foreach ($data as $value) 
        {
            $result = array(
                'userid'=>$value['userid'],
                'username'=>$value['username'],
                'avatarblobid'=>(!empty($value['avatarblobid']))?$value['avatarblobid']:''
            );

            $holeData[] = $result;
        }
        if(empty($result)){
            $resp = array('code'=>'404','message'=>'Datos no encontrados.','response'=>array('search_members'=>[]));
            $this->response($resp); 
        }
        else{
            $resp = array('code'=>'200','message'=>'Registro de miembro de búsqueda.','response'=>array('search_members'=>$holeData)); 
            $this->response($resp);
        }

    }

    public function homeSearch_post()
    {
        $search  = $this->input->post('search');
        $limit  = (($this->input->post('limit'))>0)?$this->input->post('limit'):'0';
        $offset  = (($this->input->post('offset'))>0)?$this->input->post('offset'):'5000';

        $checkData  = array('search'=>$search);
        $required_parameter = array('search');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'400','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $select = "title,postid,categoryid,userid";
        $where = "WHERE type='Q' AND userid != '' AND parentid IS NULL AND title RLIKE '[[:<:]]".$search."[[:>:]]'";
        $order = "ORDER BY postid DESC";
        $set_limit = "LIMIT $limit,$offset";

        $data = $this->model->customFetchQuery($select,'qa_posts',$where,$order,$set_limit);
        $rplc = array("\n","\"","<p>","</p>");        
        $holeData = array();
        foreach ($data as $value) 
        {
            $title= str_replace($rplc,"",$value['title']);
            $result = array(
                'postid'=>$value['postid'],
                'categoryid'=>$value['categoryid'],
                'title'=>$title,
                'userid'=>$value['userid']
            );

            $holeData[] = $result;
        }
        if(empty($result)){
            $resp = array('code'=>'404','message'=>'Post record not found','response'=>array('search_post'=>[]));
            $this->response($resp); 
        }
        else{
            $resp = array('code'=>'200','message'=>'Post search','response'=>array('search_post'=>$holeData)); 
            $this->response($resp);
        }

    }

    public function imageUplodForm_post()
    {
        $userid = $this->input->post('userid');
        $site_url = base_url();
        $path = $site_url.'uploads/';
        $all_imgs = $this->dynamicImageUploadMultiple('imageUrl','uploads/',$path);
        
        $extra_content = '';
        foreach($all_imgs as $key=>$val){
            $extra_content .= $val.' ';                    
        } 
        $where = array('title'=>'ashu','postid'=>'10486');
        $this->model->deleteQuery('qa_postmetas',$where);

        $data = array('title'=>'ashu','postid'=>'10486','content'=>$extra_content);
        $last_id = $this->model->insertQuery1('qa_postmetas',$data);
        $resp = array('code'=>'200','message'=>'Success','response'=>array('posts'=>$data,'userid'=>$userid)); 
        $this->response($resp);

    }

    public function addNewPost_post()
    {
        $site_url = base_url();//"https://www.adimvi.com//king-include/";

        $userid = $this->input->post('userid');
        $title = $this->input->post('title');
        $categoryid = $this->input->post('categoryid');        
        $tags = $this->input->post('tags');
        $description = $this->input->post('description');
        $notify = $this->input->post('notify');
        $remove_promotions = $this->input->post('remove_promotions');
        $image_promotions = $this->input->post('image_promotions');
        $type = ($this->input->post('type')=='draft')?'DRAFT':'';
        $userad   = ($this->input->post('userad')>0)?'1':'0';
        $adimviad = ($this->input->post('adimviad')>0)?'1':'0';        
        
        $price  = (($this->input->post('price'))>0)?$this->input->post('price'):'0';
        $pricer = ($price>0)?'1':'0';

        if($type=='')
        {
            $checkData  = array('userid'=>$userid,'title'=>$title,'categoryid'=>$categoryid,'tags'=>$tags,'description'=>$description);
            $required_parameter = array('userid','title','categoryid','tags','description');
            $chk_error = $this->check_required_value($required_parameter,$checkData);
            if ($chk_error) {
                $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
                @$this->response($resp); exit;
            }
        }

        $extra_content = '';
        $content = (!empty($this->input->post('videoUrl')))?$this->input->post('videoUrl'):'';
        if(!empty($_FILES['imageUrl']['name']))
        {
            $path = $site_url.'uploads/';
            $all_imgs = $this->dynamicImageUploadMultiple('imageUrl','uploads/',$path);
            
            if(count($all_imgs)<1){
                $content= $all_imgs[0];
            }
            else{                
                foreach($all_imgs as $key=>$val){
                    if($key==0){
                        $content = $val;
                    }else{
                        $extra_content .= $val.' ';
                    }                    
                }                
            } 
        }

        date_default_timezone_set('Asia/Kolkata');
        $created = date("Y-m-d H:i:s");
        $data = array(
          'userid'=>$userid,
          'title'=>$title,
          'categoryid'=>$categoryid,
          'catidpath1'=>$categoryid,
          'content'=>$content,
          'tags'=>$tags,
          'price'=>$price,
          'pricer'=>$pricer,
          'type'=>$type,
          'notify'=>($notify>0)?'@':'',
          'created'=>$created,
          'userad'=>$userad,
          'adimviad'=>$adimviad
        );

        if(($last_id = $this->model->insertQuery('qa_posts',$data))>0)
        {
            $this->session->set_userdata('LastpostID',$last_id);

            $postMeta1 = array('postid'=>$last_id,'title'=>'qa_q_extra1','content'=>$description);
            $this->model->insertQuery('qa_postmetas',$postMeta1);

            $postID = $this->session->userdata('LastpostID');
            $extra_content = (!empty($extra_content))?$extra_content:$content;
            $postMeta2 = array('postid'=>$postID,'title'=>'qa_q_extra','content'=>$extra_content);
            $this->model->insertQuery('qa_postmetas',$postMeta2);

            $params = "postid=$last_id parentid= parent= title=$title"; 
            $insert = array(
                'userid'=>$userid,
                'handle'=>'username',
                'event'=>'q_post',
                'params'=>$params,
                'ipaddress'=>'186.108.226.48',
                'datetime'=>date('Y-m-d H:i:s')
            );
            $this->model->insertQuery1('qa_eventlog',$insert);

            $this->session->unset_userdata('LastpostID');
            if($type==''){
                $msg = 'Publicacion agregada con exito.';
            }else{
                $msg = 'Publicacion guardada en borrador.';
            }
            $resp = array('code'=>'200','message'=>$msg,'response'=>$data);            
        }else{
            $resp = array('code'=>'400','message'=>'Operation failed');
        }
        @$this->response($resp); exit;
    }

    public function draftPostList_post()
    {
        $site_url = base_url();
        $userid = $this->input->post('userid');

        $checkData  = array('userid'=>$userid,);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $where = "userid=$userid AND type='DRAFT'";
        $draftPost = $this->model->fetchQuery('postid,title','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$where,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,'postid','DESC',$sLimit=NULL,$eLimit=NULL);

        if(!empty($draftPost))
        {
            $holeData=array();
            foreach($draftPost as $row)
            {
                $record=array(
                   'postid'=>$row['postid'],
                   'title'=>$row['title'],
                   'post_type'=>'draft'
                );
                $holeData[]=$record;
            }

            $resp = array('code'=>'200','message'=>'Success','draftPost'=>$holeData);
        }
        else{
            $resp = array('code'=>'400','message'=>'Datos no encontrados.','draftPost'=>[]);
        }
        @$this->response($resp);
    }

    public function draftPostDetail_post()
    {
        $site_url = base_url();
        $postid = $this->input->post('postid');
        $type = $this->input->post('type');
        $checkData  = array('postid'=>$postid,'type'=>$type);
        $required_parameter = array('postid','type');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp);
        }
        
        if($type=='publish')
        	$data= $this->model->postDataGet($postid);
        else if($type=='draft')
        	$data= $this->model->postDataGet($postid,'draft');

        if(!empty($data)) 
        {
            $post_date = date('d M',strtotime($data['post_date']));
            $post_time = date('i',strtotime($data['post_date'])).' min.';
     
            $whereMeta = array('postid'=>$postid);
            $metaData = $this->model->fetchQuery('content','qa_postmetas',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereMeta,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL);
            
            $meta_img = explode(" ",$metaData[0]['content']); 
            $extraImg = array();
            $countExtra =  count($meta_img);
            foreach($meta_img as $key=>$imgs){
                if(!empty($imgs)){
                    if($imgs!=$data['post_image'])
                        $extraImg[] = array('url'.$key=>$imgs);
                }
            }
            
            $rplc = array("\n","\"","<p>","</p>");
            $post_description= str_replace($rplc,"",$metaData[1]['content']);
            
            $rLink = array(' ',"\n","\"");
            $post_titleSh = str_replace($rLink,"-",$data['post_title']);
            $share_link = 'https://www.adimvi.com/?qa='.$data['postid'].'/'.strtolower($post_titleSh);

            $pathinfo = strtolower(pathinfo($data['post_image'], PATHINFO_EXTENSION));
            $record = array(
                'postid'=>$data['postid'],
                'userid'=>$data['userid'],
                'categoryid'=>$data['categoryid'],
                'category_name'=>$data['category_name'],
                'post_title'=>str_replace($rplc,"",$data['post_title']),            
                'post_image'=>$data['post_image'],
                'post_extra_image'=>$extraImg,
                'tags'=>str_replace(',',' ',$data['tags']),
                'price'=>$data['price'],
                'pricer'=>$data['pricer'],
                'adimvi_promotions'=>($data['adimviad']>0)?'1':'0',
                'promotional_image'=>($data['userad']>0)?'1':'0',
                'notify'=>($data['notify']=='@')?'1':'0',
                'post_description'=>(!empty($post_description))?$post_description:'',
                'post_type'=>'draft',
                'file_type'=>($pathinfo=='png'||$pathinfo=='jpeg'||$pathinfo=='jpg')?'Image':'Video'           
                
            );
        }
        if(empty($data))
            $resp = array('code'=>'404','message'=>'Post Data','response'=>array('drafPost'=>[]));
        else
            $resp = array('code' => '200', 'message' => 'SUCCESS', 'response' => array('drafPost'=>$record));                    
        $this->response($resp);

    }

    public function updatePostBackup_post()
    {
        $site_url = base_url();//"https://www.adimvi.com//king-include/";

        $userid = $this->input->post('userid');
        $postid = $this->input->post('postid');
        $title = $this->input->post('title');
        $categoryid = $this->input->post('categoryid');        
        $tags = $this->input->post('tags');
        $description = $this->input->post('description');
        $notify = $this->input->post('notify');
        $post_type     = $this->input->post('type');
        $adimviad = ($this->input->post('adimviad')>0)?'1':'0';
        $userad   = ($this->input->post('userad')>0)?'1':'0';
                

        $price  = (($this->input->post('price'))>0)?$this->input->post('price'):'0';
        $pricer = ($price>0)?'1':'0';

        $checkData  = array('userid'=>$userid,'postid'=>$postid,'type'=>$post_type);
        $required_parameter = array('userid','postid','type');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        if($post_type=='publish_edit'){
            $type='Q';
        }else if($post_type=='draft_edit'){
            $type='';
        }else if($post_type=='draft_update'){
            $type='DRAFT';
        }

        $where = array('postid'=>$postid);
        if(($this->model->countQuery('qa_posts',$where))<=0){
            $resp = array('code' => '400', 'message' => 'Invalid post id');   
            $this->response($resp); exit;
        }
        $extra_content = '';
        $content = (!empty($this->input->post('videoUrl')))?$this->input->post('videoUrl'):'';
        if(!empty($_FILES['imageUrl']['name']))
        {
            $filName = $_FILES['imageUrl']['name']; 

            if($filName[0]!='arjun.png')
            {                
                $all_imgs = $this->dynamicImageUploadMultiple('imageUrl','uploads/',$path);
                
                if(count($all_imgs)<1){
                    $content= $all_imgs[0];
                }
                else{                
                    foreach($all_imgs as $key=>$val){
                        if($key==0){
                            $content = $val;
                        }else{
                            $extra_content .= $val.' ';
                        }                    
                    }                
                }
            }
        }
        
        date_default_timezone_set('Asia/Kolkata');
        $created = date("Y-m-d H:i:s");
        $data = array(
          'userid'=>$userid,
          'title'=>$title,
          'categoryid'=>$categoryid,
          'catidpath1'=>$categoryid,
          //'content'=>$content,
          'tags'=>$tags,
          'price'=>$price,
          'pricer'=>$pricer,
          'type'=>$type,
          'notify'=>($notify>0)?'@':'',
          'created'=>$created,
          'userad'=>$userad,
          'adimviad'=>$adimviad,
          'default_img'=>$_FILES['imageUrl']['name'],
          'extra_content'=>$extra_content
        );

        if(!empty($content)){
            $data['content']=$content;
        }
        $resp = array('code'=>'200','message'=>'Test','response'=>$data);
        @$this->response($resp); exit;
        //print_r($data); die();
        if(($this->model->updateQuery('qa_posts',$data,$where))>0)
        {
            
            $where_extra1 = array('postid'=>$postid,'title'=>'qa_q_extra1');            
            $postMeta1 = array('content'=>$description);
            $this->model->updateQuery('qa_postmetas',$postMeta1,$where_extra1);

            
            $where_extra = array('postid'=>$postid,'title'=>'qa_q_extra');
            $extra_content = (!empty($extra_content))?$extra_content:$content;
            if(!empty($extra_content))
            {
                $postMeta2 = array('content'=>$extra_content);
                $this->model->updateQuery('qa_postmetas',$postMeta2,$where_extra);
            }
            
            $msg = 'Publicacion actualizada con exito.';           
            $resp = array('code'=>'200','message'=>$msg,'response'=>$data);            
        }else{
            $resp = array('code'=>'400','message'=>'Updation failed');
        }
        @$this->response($resp); exit;
    }

    public function updatePost_post()
    {
        $site_url = base_url();//"https://www.adimvi.com//king-include/";

        $userid = $this->input->post('userid');
        $postid = $this->input->post('postid');
        $title = $this->input->post('title');
        $categoryid = $this->input->post('categoryid');        
        $tags = $this->input->post('tags');
        $description = $this->input->post('description');
        $notify = $this->input->post('notify');
        $post_type = $this->input->post('type');
        $adimviad = ($this->input->post('adimviad')>0)?'1':'0';
        $userad   = ($this->input->post('userad')>0)?'1':'0';
                
        $price  = (($this->input->post('price'))>0)?$this->input->post('price'):'0';
        $pricer = ($price>0)?'1':'0';

        $checkData  = array('userid'=>$userid,'postid'=>$postid,'type'=>$post_type);
        $required_parameter = array('userid','postid','type');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        if($post_type=='publish_edit'){
            $type='Q';
        }else if($post_type=='draft_edit'){
            $type='';
        }else if($post_type=='draft_update'){
            $type='DRAFT';
        }

        $where = array('postid'=>$postid);
        if(($this->model->countQuery('qa_posts',$where))<=0){
            $resp = array('code' => '400', 'message' => 'Invalid post id');   
            $this->response($resp); exit;
        }
        $extra_content = '';
        $content = (!empty($this->input->post('videoUrl')))?$this->input->post('videoUrl'):'';
        if(!empty($_FILES['imageUrl']['name']))
        {
            $path = $site_url.'uploads/';
            $all_imgs = $this->dynamicImageUploadMultiple('imageUrl','uploads/',$path);
            
            if(count($all_imgs)<1){
                $content= $all_imgs[0];
            }
            else{                
                foreach($all_imgs as $key=>$val){
                    if($key==0){
                        $content = $val;
                    }else{
                        $extra_content .= $val.' ';
                    }                    
                }                
            } 
        }

        date_default_timezone_set('Asia/Kolkata');
        $created = date("Y-m-d H:i:s");
        $data = array(
          'userid'=>$userid,
          'title'=>$title,
          'categoryid'=>$categoryid,
          'catidpath1'=>$categoryid,
          //'content'=>$content,
          'tags'=>$tags,
          'price'=>$price,
          'pricer'=>$pricer,
          'type'=>$type,
          'notify'=>($notify>0)?'@':'',
          'created'=>$created,
          'userad'=>$userad,
          'adimviad'=>$adimviad
        );

        if(!empty($content)){
            $data['content']=$content;
        }
        
        if(($this->model->updateQuery('qa_posts',$data,$where))>0)
        {            
            $where_extra1 = array('postid'=>$postid,'title'=>'qa_q_extra1');            
            $postMeta1 = array('content'=>$description);
            $this->model->updateQuery('qa_postmetas',$postMeta1,$where_extra1);
            
            $where_extra = array('postid'=>$postid,'title'=>'qa_q_extra');
            $extra_content = (!empty($extra_content))?$extra_content:$content;
            if(!empty($extra_content))
            {
                $postMeta2 = array('content'=>$extra_content);
                $this->model->updateQuery('qa_postmetas',$postMeta2,$where_extra);
            }
            
            $msg = 'Publicacion actualizada con exito.';           
            $resp = array('code'=>'200','message'=>$msg,'response'=>$data);            
        }else{
            $resp = array('code'=>'400','message'=>'Updation failed');
        }
        @$this->response($resp); exit;
    }

    public function postViewed_post()
    {
        $postid = $this->input->post('postid');

        $checkData  = array('postid'=>$postid);
        $required_parameter = array('postid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $where = "postid=$postid AND type='Q'";
        $select = "views,userid";
        $data = current($this->model->fetchQuery($select,'qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$where));
        if(!empty($data))
        {
            $post_views = $data['views']+1;
            $db_userid  = $data['userid'];
            
            $postView = array('views'=>$post_views);

            $whereA = "userid=$db_userid";
            $selectA = "views,usd";
            $admin_data = current($this->model->fetchQuery($selectA,'qa_adimviviews',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereA));
            if(!empty($admin_data))
            {
                $adminViews = $admin_data['views']+1;
                $usd        = $admin_data['usd'];           
                $adminView = array('views'=>$adminViews);
                $this->model->updateQuery('qa_adimviviews',$adminView,$whereA);

            }else{
                $adminData = array('views'=>1,'userid'=>$db_userid,'usd'=>0);
                $this->model->insertQuery1('qa_adimviviews',$adminData);
            }
            $resp = array('code'=>'200','message'=>'Ver actualizado con exito.');
            $this->model->updateQuery('qa_posts',$postView,$where);         
        }
        else{
            $resp = array('code'=>'200','message'=>'Detalles de publicacion no validos.');
        }
        
        @$this->response($resp); exit;
    }

    public function buyPost_post()
    {
        $buyer  = $this->input->post('buyer');
        $seller = $this->input->post('seller');
        $postid = $this->input->post('postid');        
        $price  = str_replace("$","",$this->input->post('price'));
       
        $checkData  = array('buyer'=>$buyer,'seller'=>$seller,'postid'=>$postid,'price'=>$price);
        $required_parameter = array('buyer','seller','postid','price');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $andWhere = "userid=$buyer";
        $userData = current($this->model->fetchQuery('credit','qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=0,$eLimit=1));
        if($userData['credit']<$price){
           $resp = array('code'=>'501','message'=>'Su saldo actual es insuficiente.');
           @$this->response($resp); exit; 
        }

        $wherePur = array('buyer'=>$buyer,'seller'=>$seller,'postid'=>$postid);
        if(($this->model->countQuery('qa_adimvipre',$wherePur))>0){
            $resp = array('code'=>'501','message'=>'Esta publicacion ya esta comprada.');
            @$this->response($resp); exit;
        }
            

        date_default_timezone_set('Asia/Kolkata');
        $created = date("Y-m-d H:i:s");
        $newPrice = ($price*90)/100;
        $data = array(
          'buyer'=>$buyer,
          'seller'=>$seller,
          'postid'=>$postid,
          'price'=>$newPrice,
          'notify'=>'1',
          'created'=>$created
        );

        if(($this->model->insertQuery1('qa_adimvipre',$data))>0)
        {
            $updateBalance = $userData['credit']-$price;
            $udateData = array('credit'=>$updateBalance);
            $this->model->updateQuery('qa_users',$udateData,$andWhere);
            $resp = array('code'=>'200','postid'=>$postid,'buy_post'=>'1','message'=>'Publica la compra con exito.');            
        }else{
            $resp = array('code'=>'400','message'=>'La compra posterior ha fallado.');
        }
        @$this->response($resp); exit;
    }

    public function buyPostList_post()
    {
        $userid = $this->input->post('userid');
       
        $checkData  = array('userid'=>$userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $where = "WHERE ap.seller=$userid";
        $data = $this->model->getBuyPost($where);
        //echo "<pre>";
        //print_r($data); die();
        if(!empty($data))
        {
            $holeData = array();
            foreach($data as $row)
            {
                $record = array(
                   'buyerid'=>$row['buyer'],
                   'postid'=>$row['postid'],
                   'usd'=>'$'.$row['price'],
                   'username'=>$row['handle'],
                   'notify'=>$row['notify'],
                   'created'=>date('d M Y',strtotime($row['created']))
                );

                $holeData[] = $record;
            }
            $resp = array('code'=>'200','message'=>'success','response'=>array('buyPost'=>$holeData));            
        }
        else{
            $resp = array('code'=>'400','message'=>'Publicacion del comprador no encontrada.');
        }
        @$this->response($resp); exit;
    }

    public function salesNotify_post()
    {
        $userid = $this->input->post('userid');
       
        $checkData  = array('userid'=>$userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $where = "seller=$userid AND notify='1'";
        $total = $this->model->countQuery('qa_adimvipre',$where);
        if($total>0)
        {
            $record = array(
                'total_notify'=>($total>0)?$total:'0'
            );

            $resp = array('code'=>'200','message'=>'success','response'=>array('salesPost'=>$record));
        }else{
            $resp = array('code'=>'400','message'=>'No notification','response'=>array('salesPost'=>$total));
        }                    
        @$this->response($resp); exit;
    }

    public function salesNotifyUpdate_post()
    {
        $userid = $this->input->post('userid');
       
        $checkData  = array('userid'=>$userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $where = "seller=$userid AND notify='1'";
        $data = array('notify'=>0);
        $total = $this->model->updateQuery('qa_adimvipre',$data,$where);
        if($total>0){
            $resp = array('code'=>'200','message'=>'Success');
        }else{
            $resp = array('code'=>'400','message'=>'Failed');
        }                    
        @$this->response($resp); exit;
    }

    public function categoryList_post()
    {
        $select = 'categoryid,title';
        $data = $this->model->fetchQuery($select,'qa_categories',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere=NULL,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName='categoryid',$ascDsc='DESC',$sLimit=NULL,$eLimit=NULL);

        if(!empty($data))
        {
            $allCat = array();
            foreach($data as $row)
            {
                $record = array(
                   'categoryid'=>$row['categoryid'],
                   'categoryName'=>$row['title']
                );

                $allCat[] = $record;
            }
            $resp = array('code'=>'200','message'=>'SUCCESS', 'response' => array('categories'=>$allCat));            
        }
        else{
            $resp = array('code'=>'400','message'=>'Categoría no encontrada.','response' => array('categories'=>[]));
        }
        @$this->response($resp); exit;
    }  

    public function addWall_post()
    {
        $fromuserid = $this->input->post('fromuserid');
        $touserid = $this->input->post('touserid');
        $content = $this->input->post('wall_message');        

        $checkData  = array('fromuserid'=>$fromuserid,'touserid'=>$touserid,'message'=>$content);
        $required_parameter = array('fromuserid','touserid','message');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        date_default_timezone_set('Asia/Kolkata');
        $created = date("Y-m-d H:i:s");
        $data = array(
          'fromuserid'=>$fromuserid,
          'touserid'=>$touserid,
          'content'=>$content,
          'created'=>$created,          
          'type'=>'PUBLIC'
        );

        $dataPayload = array(
            'body'=>$content,
            'title'=>'Message on your wall',
            'sub_text'=>'sub_text',
            'device_type'=>'ios'            
        );

        $returnArr = $this->push_notification($dataPayload,$touserid);
        if(($this->model->insertQuery('qa_messages',$data))>0){            
            $resp = array('code'=>'200','message'=>'Mensaje de muro agregado con exito.');            
        }else{
            $resp = array('code'=>'400','message'=>'Wall adding failed');
        }
        @$this->response($resp); exit;
    }

    public function replyWallComments_post()
    {
        $fromuserid = $this->input->post('fromuserid');
        $touserid = $this->input->post('touserid');
        $messageid = $this->input->post('messageid');
        $content = $this->input->post('wall_comment');        

        $checkData  = array('messageid'=>$messageid,'fromuserid'=>$fromuserid,'touserid'=>$touserid,'comment'=>$content);
        $required_parameter = array('messageid','fromuserid','touserid','comment');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        date_default_timezone_set('Asia/Kolkata');
        $created = date("Y-m-d H:i:s");
        $data = array(
          'fromuserid'=>$fromuserid,
          'touserid'=>$touserid,
          'content'=>$content,
          'created'=>$created,
          'level'=>$messageid,
          'type'=>'WREPLY'
        );

        $dataPayload = array(
            'body'=>$content,
            'title'=>'Comment on your wall',
            'sub_text'=>'sub_text',
            'device_type'=>'ios'            
        );

        $returnArr = $this->push_notification($dataPayload,$touserid);

        if(($this->model->insertQuery('qa_messages',$data))>0){            
            $resp = array('code'=>'200','message'=>'Comentario agregado con exito.');            
        }else{
            $resp = array('code'=>'400','message'=>'Comment adding failed');
        }
        @$this->response($resp); exit;
    }

    public function editWall_post()
    {
        $messageid = $this->input->post('messageid');
        $content = $this->input->post('wall_message');        

        $checkData  = array('messageid'=>$messageid,'message'=>$content);
        $required_parameter = array('messageid','message');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $data = array('content'=>$content);
        $where = "messageid=$messageid";
        if(($this->model->updateQuery('qa_messages',$data,$where))>0){            
            $resp = array('code'=>'200','message'=>'Mensaje de muro actualizado correctamente.');            
        }else{
            $resp = array('code'=>'400','message'=>'Wall updation failed');
        }
        @$this->response($resp); exit;
    }

    public function agoTimeSetBackup($ptime)
    {
        
        $estimate_time = time() - $ptime; 
        if($estimate_time < 1){
            return 'just now';
        }

        $condition = array( 
            12 * 30 * 24 * 60 * 60  =>  'year',
            30 * 24 * 60 * 60       =>  'month',
            24 * 60 * 60            =>  'day',
            60 * 60                 =>  'hour',
            60                      =>  'minute',
            1                       =>  'second'
        );

        foreach($condition as $secs => $str)
        {
            $d = $estimate_time/$secs;
            if($d>=1){
                $r = round($d);
                return $r.' ' .$str.($r>1?'s':'').' ago';
            }
        }
        
    }


    public function agoTimeSet($ptime)
    {
        
        $estimate_time = time() - $ptime; 
        if($estimate_time < 1){
            return 'justo ahora';
        }

        $condition = array( 
            12 * 30 * 24 * 60 * 60  =>  'años',
            30 * 24 * 60 * 60       =>  'meses',
            24 * 60 * 60            =>  'días',
            60 * 60                 =>  'horas',
            60                      =>  'minuto',
            1                       =>  'segundos'
        );

        foreach($condition as $secs => $str)
        {
            $d = $estimate_time/$secs;
            if($d>=1){
                $r = round($d);
                return 'Hace '. $r.' ' .$str.($r>1?'':'');
            }
        }
        
    }

    public function wallPostList_post()
    {        
        $touserid = $this->input->post('touserid');
        $checkData  = array('touserid'=>$touserid); //wall post user id, not (login userId)
        $required_parameter = array('touserid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $data = $this->model->wallMessageList($touserid); 
        $postData = array(); 
        foreach($data as $row)
        {           
            date_default_timezone_set('Asia/Kolkata');
            $created=$this->agoTimeSet(strtotime($row['created'])); 

            $messageid = $row['messageid'];
            $whereCount = "level=$messageid AND type='WREPLY'";
            $totalReply = $this->model->countQuery('qa_messages',$whereCount);   

            $whereFav = array('userid'=>$touserid,'entityid'=>$messageid,'entitytype'=>'M','nouserevents'=>0);
            if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
                $wall_favourite ='1';
            else
                $wall_favourite ='0';
                
            $whereFav = array('entityid'=>$messageid,'entitytype'=>'M','nouserevents'=>0);
            if(($total = $this->model->countQuery('qa_userfavorites',$whereFav))>0)
                $total_favourite =$total;
            else
                $total_favourite='0';
            

            $record = array(
                'messageid'=>$row['messageid'],
                'fromuserid'=>$row['fromuserid'], // login userid
                'touserid'=>$row['touserid'], //wall post userid
                'username'=>$row['handle'],
                'favourite'=>$wall_favourite,
                'total_favourite'=>$total_favourite,
                'totalComments'=>($totalReply>0)?"$totalReply":"0",
                'avatarblobid'=>(!empty($row['avatarblobid']))?$row['avatarblobid']:'',
                'content'=>$row['content'],
                'created'=>$created,
                'filter_date'=>$row['created']                
            );
            $postData[] = $record;

            $created = array_column($postData, 'filter_date');
            array_multisort($created, SORT_ASC, SORT_STRING, $postData, SORT_ASC, SORT_NUMERIC);
        }

        if((!empty($data))){            
            $resp = array('code'=>'200','message'=>'SUCCESS','response'=>array('wallPost'=>$postData));            
        }else{
            $resp = array('code'=>'400','message'=>'Wall post data not found','response'=>array('wallPost'=>[]));
        }
        @$this->response($resp); exit;
    }

    public function wallCommentList_post()
    {        
        $messageid = $this->input->post('messageid');
        $checkData  = array('messageid'=>$messageid);
        $required_parameter = array('messageid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }
        
        $repalyWall = array();
        if(!empty($dataRpy=$this->model->wallMessageReply($messageid))){
            foreach($dataRpy as $rep)
            {
                $record = array(
                    'messageid'=>$rep['messageid'],
                    'userid'=>$rep['fromuserid'],
                    'username'=>$rep['handle'],
                    'avatarblobid'=>(!empty($rep['avatarblobid']))?$rep['avatarblobid']:'',
                    'content'=>$rep['content']                        
                );
                $repalyWall[] = $record;
            }
        }
              
        if((!empty($repalyWall))){            
            $resp = array('code'=>'200','message'=>'success','response'=>array('wallComments'=>$repalyWall));            
        }else{
            $resp = array('code'=>'400','message'=>'Wall comment data not found.','response'=>array('wallComments'=>[]));
        }
        @$this->response($resp); exit;
    }

    public function wallFollowList_post()
    {        
        
        $login_userid = $this->input->post('userid');
        $checkData  = array('userid'=>$login_userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $whereFlw = "userid=$login_userid AND entitytype='U'";
        $fData = $this->model->fetchQuery('entityid','qa_userfavorites',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereFlw,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=0,$eLimit=10000);
        //print_r( $fData); die();
        $postData = array();
        foreach($fData as $fDa)
        {
            $data = $this->model->followWallMessageList($fDa['entityid'],$login_userid);             
            
            if(!empty($data))
            {                 
                foreach($data as $row)
                {           
                    date_default_timezone_set('Asia/Kolkata');
                    $created=$this->agoTimeSet(strtotime($row['created'])); 

                    $messageid = $row['messageid'];
                    $whereCount = "level=$messageid AND type='WREPLY'";
                    $totalReply = $this->model->countQuery('qa_messages',$whereCount);   

                    $whereFav = array('userid'=>$login_userid,'entityid'=>$messageid,'entitytype'=>'M','nouserevents'=>0);
                    if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
                        $wall_favourite ='1';
                    else
                        $wall_favourite ='0';
                        
                    $whereFav = array('entityid'=>$messageid,'entitytype'=>'M','nouserevents'=>0);
                    if(($total = $this->model->countQuery('qa_userfavorites',$whereFav))>0)
                        $total_favourite =$total;
                    else
                        $total_favourite='0';
                    
                    $record = array(
                        'messageid'=>$row['messageid'],
                        'fromuserid'=>$row['fromuserid'],
                        'touserid'=>$row['touserid'], 
                        'username'=>$row['handle'],
                        'favourite'=>$wall_favourite,
                        'total_favourite'=>$total_favourite,
                        'totalComments'=>($totalReply>0)?"$totalReply":"0",
                        'avatarblobid'=>(!empty($row['avatarblobid']))?$row['avatarblobid']:'',
                        'content'=>$row['content'],
                        'created'=>$created,
                        'filter_date'=>$row['created']                
                    );
                    $postData[] = $record;
                }
            }
        }

        $created = array_column($postData, 'filter_date');
        array_multisort($created, SORT_ASC, SORT_STRING, $postData, SORT_ASC, SORT_NUMERIC);

        if((!empty($postData))){            
            $resp = array('code'=>'200','message'=>'SUCCESS','response'=>array('wallFollowPost'=>$postData));            
        }else{
            $resp = array('code'=>'400','message'=>'Wall follow post data not found','response'=>array('wallFollowPost'=>[]));
        }
        @$this->response($resp); exit;
    }

    public function wallFollowList_post_backup()
    {        
        
        $login_userid = $this->input->post('userid');
        $checkData  = array('userid'=>$login_userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $whereFlw = "userid=$login_userid AND entitytype='U'";
        $fData = $this->model->fetchQuery('entityid','qa_userfavorites',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereFlw,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=0,$eLimit=10000);
        //print_r( $fData); die();
        $postData = array();
        foreach($fData as $fDa)
        {
            $data = $this->model->followWallMessageList($fDa['entityid']);             
            
            if(!empty($data))
            {                 
                foreach($data as $row)
                {           
                    date_default_timezone_set('Asia/Kolkata');
                    $created=$this->agoTimeSet(strtotime($row['created'])); 

                    $messageid = $row['messageid'];
                    $whereCount = "level=$messageid AND type='WREPLY'";
                    $totalReply = $this->model->countQuery('qa_messages',$whereCount);   

                    $whereFav = array('userid'=>$login_userid,'entityid'=>$messageid,'entitytype'=>'M','nouserevents'=>0);
                    if(($this->model->countQuery('qa_userfavorites',$whereFav))>0)
                        $wall_favourite ='1';
                    else
                        $wall_favourite ='0';
                        
                    $whereFav = array('entityid'=>$messageid,'entitytype'=>'M','nouserevents'=>0);
                    if(($total = $this->model->countQuery('qa_userfavorites',$whereFav))>0)
                        $total_favourite =$total;
                    else
                        $total_favourite='0';
                    
                    $record = array(
                        'messageid'=>$row['messageid'],
                        'fromuserid'=>$row['fromuserid'],
                        'touserid'=>$row['touserid'], 
                        'username'=>$row['handle'],
                        'favourite'=>$wall_favourite,
                        'total_favourite'=>$total_favourite,
                        'totalComments'=>($totalReply>0)?"$totalReply":"0",
                        'avatarblobid'=>(!empty($row['avatarblobid']))?$row['avatarblobid']:'',
                        'content'=>$row['content'],
                        'created'=>$created,
                        'filter_date'=>$row['created']                
                    );
                    $postData[] = $record;
                }
            }
        }

        $created = array_column($postData, 'filter_date');
        array_multisort($created, SORT_ASC, SORT_STRING, $postData, SORT_ASC, SORT_NUMERIC);

        if((!empty($postData))){            
            $resp = array('code'=>'200','message'=>'SUCCESS','response'=>array('wallFollowPost'=>$postData));            
        }else{
            $resp = array('code'=>'400','message'=>'Wall follow post data not found','response'=>array('wallFollowPost'=>[]));
        }
        @$this->response($resp); exit;
    }

    public function deleteWall_post()
    {
        $messageid = $this->input->post('messageid');        
        $checkData  = array('messageid'=>$messageid);
        $required_parameter = array('messageid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $whereReply = array('level'=>$messageid);
        $this->model->deleteQuery('qa_messages',$whereReply);

        $whereWall = array('messageid'=>$messageid);
        if(($this->model->deleteQuery('qa_messages',$whereWall))>0){            
            $resp = array('code'=>'200','message'=>'Mensaje de muro eliminado correctamente.');            
        }else{
            $resp = array('code'=>'400','message'=>'Deletion failed');
        }
        @$this->response($resp); exit;
    }

    public function setWallfavourite_post()
    {
        $userid = $this->input->post('userid');
        $messageid = $this->input->post('messageid');        
        $checkData  = array('userid'=>$userid,'messageid'=>$messageid);
        $required_parameter = array('userid','messageid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $where = array(
            'userid' => $userid,
            'entityid' => $messageid,
            'entitytype' => 'M'
        );
        if(($this->model->countQuery('qa_userfavorites',$where))<=0)
        {
            $data = array(
                'userid' => $userid,
                'entitytype' => 'M',
                'entityid' => $messageid,
                'nouserevents' => 0
            );
            $this->model->insertData('qa_userfavorites',$data);
            $resp = array('status'=>'1','code'=>'SUCCESS','message'=>'Muro favorito con exito.');            
        }
        else{
            $this->model->deleteQuery('qa_userfavorites',$where);
            $resp = array('status'=>'0','code'=>'SUCCESS','message'=>'Muro favorito eliminado.');   
        }
        $this->response($resp);
    }

    public function getFollowers_post()
    {
        $userid = $this->input->post('userid');
        $checkData = array('userid' => $userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code' => '501', 'message' => 'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $where = array('userid'=> $userid);
        $data = $this->model->getFollowersList($userid);

        $holeData = array();
        foreach($data as $key=>$row)
        {
            if(!empty($row['avatarblobid'])){
                $avatarblobid = $row['avatarblobid'];
            }else{
                $avatarblobid = '';
            }

            if(!empty($row['userid']) && !empty($row['userid']))
            {
                $record = array(
                    "avatarblobid"=>$avatarblobid,
                    "userid"=>$row['userid'],                
                    "username"=>$row['username'],
                    'total_points'=>$row['points']               
                );
                $holeData[] = $record;
            }            
        }
       
        if(empty($data)){
            $resp = array('code' => '400', 'message'=>'Failure','response'=>array('followers'=>'No Followers'));
            @$this->response($resp);   
        }
        else {
            $resp = array('code' => '200', 'message' => 'SUCCESS','response' => array('followers' => $holeData));   
            $this->response($resp); 
        }
    }

    public function addPrivateMessages_post()
    {                
        $fromuserid = $this->input->post('fromuserid');
        $touserid   = $this->input->post('touserid');
        $content    = $this->input->post('content');
        $imageUrl   = $this->input->post('imageUrl');

        $checkData  = array('fromuserid'=>$fromuserid,'touserid'=>$touserid);
        $required_parameter = array('fromuserid','touserid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $imageUrl = '<p><img src="'.$imageUrl.'"/></p>';

        date_default_timezone_set('Asia/Kolkata');
        $created=date('Y-m-d H:i:s'); 

        $data = array(
            'fromuserid'=>$fromuserid, 
            'touserid'=>$touserid,
            'content'=>($content=='')?$imageUrl:$content,
            'created'=>$created
        );         

        $dataPayload = array(
            'body'=>$content,
            'title'=>'Private message',
            'sub_text'=>'sub_text',
            'device_type'=>'ios'            
        );

        $returnArr = $this->push_notification($dataPayload,$touserid);

        if(($this->model->insertQuery('qa_messages',$data))>0){            
            $resp = array('code'=>'200','message'=>'Mensaje privado enviado con exito.');            
        }else{
            $resp = array('code'=>'400','message'=>'Message sending failed.');
        }
        @$this->response($resp); exit;
    }

    public function fromUserData($login_userid)
    {
        $data = $this->model->getAllPrivateMsgFrom($login_userid);

        //print_r($data); die();
        $holeData = array();
        foreach($data as $key=>$row)
        {
            if(!empty($row['from_user_avatarblobid'])){
                $from_user_avatarblobid = $row['from_user_avatarblobid'];
            }else{
                $from_user_avatarblobid = '';
            }

            if(!empty($row['to_user_avatarblobid'])){
                $to_user_avatarblobid = $row['to_user_avatarblobid'];
            }else{
                $to_user_avatarblobid = '';
            }

            date_default_timezone_set('Asia/Kolkata');
            $created=$this->agoTimeSet(strtotime($row['created']));
            if(!empty($row['fromuserid']) && !empty($row['touserid']))
            {                
                
                $image_url = (strpos($row['content'],'img src')==true)?'1':'0';
                
                $rep = array("<p>","</p>","<img src=","/>","\"");
                $content = str_replace($rep,"", $row['content']);
                $record = array(
                    "messageid"=>$row['messageid'],
                    "from_user_avatarblobid"=>$from_user_avatarblobid,
                    "to_user_avatarblobid"=>$to_user_avatarblobid, 
                    'from_userid'=>$row['from_userid'],
                    'to_userid'=>$row['to_userid'],               
                    'from_user'=>$row['from_user'],
                    'to_user'=>$row['to_user'],                    
                    'image_status'=>$image_url,
                    'content'=>$content,
                    'created'=>$created,
                    'filter_date'=>$row['created']               
                );
                $holeData[] = $record;
            
            }            
        }

        return $holeData;
    }



    public function allPrivateMessagesList_post()
    {
        $login_userid = $this->input->post('userid');        
        
        $checkData  = array('userid'=>$login_userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $data = $this->model->getAllPrivateMsg($login_userid);

        //print_r($data); die();
        $holeData = array();
        if(!empty($data))
        {
            foreach($data as $key=>$row)
            {
                if(!empty($row['from_user_avatarblobid'])){
                    $from_user_avatarblobid = $row['from_user_avatarblobid'];
                }else{
                    $from_user_avatarblobid = '';
                }

                if(!empty($row['to_user_avatarblobid'])){
                    $to_user_avatarblobid = $row['to_user_avatarblobid'];
                }else{
                    $to_user_avatarblobid = '';
                }

                date_default_timezone_set('Asia/Kolkata');
                $created=$this->agoTimeSet(strtotime($row['created']));
                if(!empty($row['fromuserid']) && !empty($row['touserid']))
                {                
                    
                    $image_url = (strpos($row['content'],'img src')==true)?'1':'0';
                    
                    $rep = array("<p>","</p>","<img src=","/>","\"");
                    $content = str_replace($rep,"", $row['content']);
                    $record = array(
                        "messageid"=>$row['messageid'],
                        "from_user_avatarblobid"=>$from_user_avatarblobid,
                        "to_user_avatarblobid"=>$to_user_avatarblobid, 
                        'from_userid'=>$row['from_userid'],
                        'to_userid'=>$row['to_userid'],               
                        'from_user'=>$row['from_user'],
                        'to_user'=>$row['to_user'],                    
                        'image_status'=>$image_url,
                        'content'=>$content,
                        'created'=>$created,
                        'filter_date'=>$row['created']               
                    );
                    $holeData[] = $record;
                
                }            
            }
        }
        
        if(empty($holeData)){
            $resp = array('code' => '400', 'message' => 'Failure', 'response' => array('message' => 'No private messages'));
            @$this->response($resp);   
        }
        else {
            $resp = array('code' => '200', 'message' => 'SUCCESS', 'response' => array('message' => $holeData));   
            $this->response($resp); 
        }
    }

    public function privateMessagesList_post()
    {
        $fromuserid = $this->input->post('fromuserid');
        $touserid = $this->input->post('touserid');
        
        $checkData  = array('fromuserid'=>$fromuserid,'touserid'=>$touserid);
        $required_parameter = array('fromuserid','touserid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $data = $this->model->getPrivateMsg($fromuserid,$touserid);

        $holeData = array();
        foreach($data as $key=>$row)
        {
            if(!empty($row['from_user_avatarblobid'])){
                $from_user_avatarblobid = $row['from_user_avatarblobid'];
            }else{
                $from_user_avatarblobid = '';
            }

            if(!empty($row['to_user_avatarblobid'])){
                $to_user_avatarblobid = $row['to_user_avatarblobid'];
            }else{
                $to_user_avatarblobid = '';
            }

            date_default_timezone_set('Asia/Kolkata');
            $created=$this->agoTimeSet(strtotime($row['created']));
            if(!empty($row['fromuserid']) && !empty($row['touserid']))
            {                
                $image_url = (strpos($row['content'],'img src')==true)?'1':'0';
                
                $rep = array("<p>","</p>","<img src=","/>","\"");
                $content = str_replace($rep,"", $row['content']);
                $record = array(
                    "messageid"=>$row['messageid'],
                    "from_user_avatarblobid"=>$from_user_avatarblobid,
                    "to_user_avatarblobid"=>$to_user_avatarblobid, 
                    'from_userid'=>$row['from_userid'],
                    'to_userid'=>$row['to_userid'],               
                    'from_user'=>$row['from_user'],
                    'to_user'=>$row['to_user'],                    
                    'image_status'=>$image_url,
                    'content'=>$content,
                    'created'=>$created               
                );
                $holeData[] = $record;
            }            
        }
       
        if(empty($data)){
            $resp = array('code' => '400', 'message' => 'Failure', 'response' => array('message' => 'No private messages'));
            @$this->response($resp);   
        }
        else {
            $resp = array('code' => '200', 'message' => 'SUCCESS', 'response' => array('message' => $holeData));   
            $this->response($resp); 
        }
    }

    public function userRecentActivity_post()
    {
        $login_userid = $this->input->post('userid');
        
        $checkData  = array('userid'=>$login_userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }
        $rplc = array("\n","\"");

        $select = "postid,parentid,type,title,created";
        $andWhere = "type='Q' OR type='A' OR type='C'";
        $data = $this->model->fetchQuery($select,'qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy='',$orderName='created',$ascDsc='DESC',$sLimit=0,$eLimit=12);
       
        $holeData = array();
        foreach($data as $key=>$row)
        {
            $postid = $row['postid'];
            
            if($row['type']=='Q')
            {
                $title  = str_replace($rplc,"",$row['title']);
                $postid = $row['postid'];
                $created=$this->agoTimeSet(strtotime($row['created']));
                $record = array('postid'=>$postid,'title'=>$title,'type'=>'Q','msg'=>'Publicaciones','created'=>$created);
                $holeData[] = $record;
            }

            if($row['type']=='A')
            {
                $parentid = $row['parentid'];
                $andWhereA = "postid=$parentid AND type='Q'";
                if($parentid>0)
                {
                    $dataA = current($this->model->fetchQuery($select,'qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhereA));

                    $titleA  = str_replace($rplc,"",$dataA['title']);
                    $created=$this->agoTimeSet(strtotime($row['created']));
                    $recordA = array('postid'=>$dataA['postid'],'title'=>$titleA,'type'=>'A','msg'=>'Respondido','created'=>$created);
                    $holeData[] = $recordA;
                }
            }

            if($row['type']=='C')
            {
                $parentidC = $row['parentid'];
                $andWhereC = "postid=$parentidC AND type='A'";
                
                $dataCp = current($this->model->fetchQuery('parentid','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhereC));

                $a_parentid = $dataCp['parentid'];
                
                $andWhereP ="postid=$a_parentid AND type='Q'"; 
                
                if($a_parentid>0)
                {
                    $dataP = current($this->model->fetchQuery($select,'qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhereP));

                    if($dataP['postid']>0)
                    {
                        $titleP  = str_replace($rplc,"",$dataP['title']);
                        $created=$this->agoTimeSet(strtotime($row['created']));
                        $recordP = array('postid'=>$dataP['postid'],'title'=>$titleP,'type'=>'C','msg'=>'Comentado','created'=>$created);
                        $holeData[] = $recordP;
                    }
                }
            }

        }
        
        //echo "<pre>";
        //print_r($holeData); die();
        if(!empty($holeData)){
            $resp = array('status'=>'1','code'=>'success','message'=>'User recent activites.','response'=>array('activity'=>$holeData)); 
        }                   
        else{
            $resp = array('status'=>'0','code'=>'failed','message'=>'No user recent activity.','response'=>array('activity'=>[]));   
        }
        $this->response($resp);
    }

    public function contact_post()
    {
        $userid   = $this->input->post('userid');
        $comment  = $this->input->post('comment');
        $username = $this->input->post('username');
        $email    = $this->input->post('email');

        $checkData  = array('comment'=>$comment,'userid'=>$userid);
        $required_parameter = array('userid','comment');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        date_default_timezone_set('Asia/Kolkata');
        $created=date('Y-m-d H:i:s');
        $data = array(
            'userid'    =>$userid,
            'comment'   =>$comment,
            'username'  =>$username,
            'email'     =>$email
        );
        $resp = array('status'=>'1','code'=>'success','message'=>'Comentarios enviados con exito.','data'=>array($data));
        $this->response($resp); exit;

        if(($this->model->insertData('qa_userfavorites',$data))>0){
            $resp = array('status'=>'1','code'=>'success','message'=>'Comentarios enviados con exito.'); 
        }                   
        else{
            $resp = array('status'=>'0','code'=>'failed','message'=>'Feedback sending failed.');   
        }
        $this->response($resp);
    }


    public function mainNotification_post()
    {
        date_default_timezone_set('Asia/Kolkata');
        $site_url = base_url();
        $site_url = "https://adimvi.com/?qa=image&qa_blobid=";

        $login_userid = $this->input->post('userid');
        
        $checkData  = array('userid'=>$login_userid);
        $required_parameter = array('userid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing ' . ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $userWhere = "userid=$login_userid";
        $selectUser = 'handle';
        $userData = current($this->model->fetchQuery($selectUser,'qa_users',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$userWhere));
        $login_userName = $userData['handle'];

        $data = $this->model->wallNofity($login_userid,'PUBLIC'); 
        $notifyData = array();

        foreach($data as $row)
        {                       
            $created=$this->agoTimeSet(strtotime($row['created'])); 
            if($row['type']=='PUBLIC'){
                //$msgNofify = "Answer on your $login_userName wall";
                $senderName = ucwords($row['handle']);
                $msgNofify = "Comment on your wall from $senderName";
                $status = '1';
            }
            else if($row['type']=='PRIVATE'){
                $senderName = ucwords($row['handle']);
                $msgNofify = "Private message from $senderName";
                $status = '2';
            }
            else if($row['type']=='WREPLY'){
                $senderName = ucwords($row['handle']);
                //$msgNofify = "Answer on your wall from $senderName";
                $msgNofify = "Response on your $senderName wall";
                $status = '3';
            }

            $record = array(
                'messageid'=>$row['messageid'],
                'message'=>$msgNofify,
                'fromuserid'=>$row['fromuserid'], // other userid
                'login_userid'=>$row['touserid'], // login userid
                'type'=>$row['type'],
                'status'=>$status,
                'username'=>$row['handle'],
                'created'=>$created,
                'postid'=>'',
                'filter_date'=>$row['created']              
            );
            $notifyData[] = $record;
        }

        $followList = $this->model->getFollowersList($login_userid);
        foreach($followList as $key=>$rowFl)
        {
            $followUname = ucwords($rowFl['username']);
            $status = '4';
            $recordF = array(
                'messageid'=>'', 
                'message'=>$followUname.' started following you',
                "fromuserid"=>$rowFl['userid'],    //other user id
                'login_userid'=>'',    
                'type'=>'following',
                'status'=>$status,
                "username"=>$rowFl['username'],
                'created'=>'',
                'postid'=>'',
                'filter_date'=>date('Y-m-d')                
            );
            $notifyData[] = $recordF;
        }

        $select = "postid,title";
        $andWhere = "userid=$login_userid AND type='Q' AND parentid IS NULL";
        $postList = $this->model->postNotify($login_userid);

        if(!empty($postList))
        {
            foreach($postList as $pd)
            {
                $postid = $pd['postid'];
                $title = $pd['title'];
                $created=$this->agoTimeSet(strtotime($pd['created']));
                $status = '5';
                
                $recordP = array(
                    'messageid'=>'', 
                    'message'=>$title,
                    "fromuserid"=>'',  
                    'login_userid'=>'',    
                    'type'=>'post_comment',
                    'status'=>$status,
                    "username"=>'',
                    'created'=>$created,
                    'postid'=>$postid,
                    'filter_date'=>$pd['created']               
                );
                $notifyData[] = $recordP;
            }
        }

        $created = array_column($notifyData, 'filter_date');
        array_multisort($created, SORT_DESC, SORT_STRING, $notifyData, SORT_DESC, SORT_NUMERIC);
        
        //echo "<pre>"; print_r($notifyData); die();

        $totalNotify = count($notifyData);
        $resp = array('code'=>'200','message'=>'Success','totalNotify'=>"$totalNotify",'response'=>array('notify'=>$notifyData));
        @$this->response($resp); exit;
    }

    public function postDelete_post()
    {
        $postid = $this->input->post('postid');
        
        $checkData  = array('postid'=>$postid);
        $required_parameter = array('postid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $wherePost = array('postid'=>$postid);
        $whereParent = array('parentid'=>$postid);
        $this->model->deleteQuery('qa_postmetas',$wherePost);
        $this->model->updateQuery('qa_posts',array('parentid'=>NULL),$whereParent);

        $this->model->deleteQuery('qa_posts',$wherePost);
        $resp = array('code'=>'200','message'=>'La publicacion se elimino correctamente.');            
        
        @$this->response($resp);
    }

    public function postDeleteBackup_post()
    {
        $postid = $this->input->post('postid');
        
        $checkData  = array('postid'=>$postid);
        $required_parameter = array('postid');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp);
        }

        $wherePost = array('postid'=>$postid);
        $whereParent = array('parentid'=>$postid);
        $this->model->deleteQuery('qa_postmetas',$wherePost);

        
        $getId = $this->model->fetchQuery('postid','qa_posts',$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$whereParent);
        $allPostId = array(); //foreign key not delete, so before parentid null and get all postid, then delete postid according of parent id
        //print_r($getId); die();
        if(!empty($getId)){        	

        	foreach($getId as $id){
        		$allPostId[] = array('postid'=>$id['postid']);
        	}

        	$this->model->updateQuery('qa_posts',array('parentid'=>NULL),$whereParent);
        	foreach($allPostId as $post_id){
        		$where_post = array('postid'=>$post_id['postid']);
        		$this->model->deleteQuery('qa_posts',$where_post);
        	}
        }

        $this->model->deleteQuery('qa_posts',$wherePost);
        $resp = array('code'=>'200','message'=>'La publicacion se elimino correctamente.');            
        
        @$this->response($resp);
    }
    
    function notificationFire_post()
    {

        $user_id     = $this->input->post('user_id');
        $device_type  = $this->input->post('device_type');
        
        $checkData  = array('user_id'=>$user_id,'device_type'=>$device_type);
        $required_parameter = array('user_id','device_type');
        $chk_error = $this->check_required_value($required_parameter,$checkData);
        if ($chk_error) {
            $resp = array('code'=>'501','message'=>'Missing '.ucwords($chk_error['param']));
            @$this->response($resp); exit;
        }

        $data = array(
            'body'=>'Test Body',
            'title'=>'Title',
            'sub_text'=>'sub_text',
            'device_type'=>$device_type            
        );

        $returnArr = $this->push_notification($data,$user_id);
        $character = json_decode($returnArr);
        //print_r($character); die();
        
        if($character->success == 1){
          $is_send = 1;
          $resp = array ('status' => "true", 'message' => "success", 'type' => $is_send , 'response' => $data);
        }
        else{
          $is_send = 0;
          $resp = array ('status' => "true", 'message' => "failure", 'type' => $is_send , 'response' => $data);
        }
      
      $this->response($resp);
    }
}
