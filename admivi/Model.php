<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');
class Model{
    var $CI;
    public function __construct($params = array()){
        $this->CI =& get_instance();
        $this->CI->load->helper('url');
        $this->CI->config->item('base_url');
        $this->CI->load->library('session', 'form_validation');
        $this->CI->load->database();
    }
    function login($username, $password){
        $this->CI->db->select('id, email, password,first_name,last_name,user_role');
        $this->CI->db->from('users');
        $this->CI->db->where('email', $username);
        $this->CI->db->where('password', ($password));
        $this->CI->db->where('user_role', '2');
        $this->CI->db->limit(1);
         $query = $this->CI->db->get();
        if($query -> num_rows() == 1){
            return $query->result();
        }
        else{
            return false;
        }
    }
     
    public function getAllRecordsById($table,$conditions){
        $this->CI->db->order_by('id', 'desc');
        $query = $this->CI->db->get_where($table,$conditions);
        return $query->result_array();
    }
    public function insertData($table, $dataInsert){
        $this->CI->db->insert($table, $dataInsert);
        return $this->CI->db->insert_id();
    }
    public function updateFields($table, $data, $where){
        return $this->CI->db->update($table, $data, $where);
    }
    public function get_matching_record($table, $val){
        $this->CI->db->select('*');
        $this->CI->db->from($table);
        $this->CI->db->where("port_description LIKE '%$val%'");
        $q   = $this->CI->db->get();
        $num = $q->num_rows();
        if ($num > 0) {
            foreach ($q->result() as $rows) {
                $data[] = $rows;
            }
            $q->free_result();
            return $data;
        }
    }
    public function getsingle($table, $where = '', $fld = NULL, $order_by = '', $order = ''){
        if ($fld != NULL) {
            $this->CI->db->select($fld);
        }
        if ($where != '') {
            $this->CI->db->where($where);
        }
        if ($order_by != '') {
            $this->CI->db->order_by($order_by, $order);
        }
        $this->CI->db->limit(1);
        $q   = $this->CI->db->get($table);
        $num = $q->num_rows(); 
        if ($num > 0) {
            return $q->row();
        }
    }
    public function GetJoinRecord($table, $field_first, $tablejointo, $field_second, $field_val, $where, $group_by = null, $order_by = null, $order = null,$limit = null, $offset = null){
        if (!empty($field_val)) {
            $this->CI->db->select("$field_val");
        } 
        else {
            $this->CI->db->select("*");
        }
        $this->CI->db->from("$table");
        $this->CI->db->join("$tablejointo", "$tablejointo.$field_second = $table.$field_first");
        if (!empty($where)) {
            $this->CI->db->where($where);
        }
        if (!empty($group_by)) {
            $this->CI->db->group_by("$table.$field_first");
        }
        if ($order_by != '') {
            $this->CI->db->order_by($order_by, $order);
        }
        $q = $this->CI->db->get();
     // echo $this->CI->db->last_query(); die;
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $rows) {
                $data[] = $rows;
            }
            $q->free_result();
            return $data;
        }
    }
    public function GetJoinRecord_new($table, $field_first, $tablejointo, $field_second, $field_val, $where, $group_by = null, $order_by = null, $order = null){
        if (!empty($field_val)) {
            $this->CI->db->select("$field_val");
        } 
        else {
            $this->CI->db->select("*");
        }
        $this->CI->db->from("$table");
        $this->CI->db->join("$tablejointo", "$tablejointo.$field_second = $table.$field_first");
        if (!empty($where)) {
            $this->CI->db->where($where);
        }
        if (!empty($group_by)) {
            $this->CI->db->group_by("$table.$group_by");
        }
        if ($order_by != '') {
            $this->CI->db->order_by($order_by, $order);
        }
        $q = $this->CI->db->get();
        //echo $this->CI->db->last_query();die();
        if ($q->num_rows() > 0) {
            foreach ($q->result() as $rows) {
                $data[] = $rows;
            }
            $q->free_result();

            return $data;
        }
    }
    public function GetJoinRecord1($table, $field_first, $tablejointo, $field_second, $field_val, $where, $group_by = null, $order_by = null, $order = null){
        if (!empty($field_val)) {
            $this->CI->db->select("$field_val");
        } 
        else {
            $this->CI->db->select("*");
        }
        $this->CI->db->from("$table");
        $this->CI->db->join("$tablejointo", "$tablejointo.$field_second = $table.$field_first");
        if (!empty($where)) {
            $this->CI->db->where($where);
        }
        if (!empty($group_by)) {
            $this->CI->db->group_by("$table.$field_first");
        }
        if ($order_by != '') {
            $this->CI->db->order_by($order_by, $order);
        }
        $q = $this->CI->db->get();
        

        if ($q->num_rows() > 0) {
            foreach ($q->result() as $rows) {
                $data[] = $rows;
            }
            $q->free_result();
            return $data;
        }
    }
    public function getAllwhere($table, $where = '', $select = 'all', $order_fld = '', $order_type = '', $limit = '', $offset = '', $or_where = null){
        if ($order_fld != '' && $order_type != '') {
            $this->CI->db->order_by($order_fld, $order_type);
        }
        if ($select == 'all') {
            $this->CI->db->select('*');
        } else {
            $this->CI->db->select($select);
        }
        if ($where != '') {
            $this->CI->db->where($where);
        }
        if ($or_where != '') {
            $this->CI->db->or_where($or_where);
        }
        if ($limit != '' && $offset != '') {
            $this->CI->db->limit($limit, $offset);
        } else if ($limit != '') {
            $this->CI->db->limit($limit);
        }
        $q        = $this->CI->db->get($table);
        //echo $this->CI->db->last_query();die;
        $num_rows = $q->num_rows();
        if ($num_rows > 0) {
            foreach ($q->result() as $rows) {
                $data[] = $rows;
            }
            $q->free_result();
            return $data;
        }
    }
    public function self_join_records($patient_id, $doctor_id){
        $this->CI->db->select('CONCAT(T1.first_name," ",T1.last_name) as doctor_first_name,CONCAT(T2.first_name," ",T2.last_name) as patient_first_name');
        $this->CI->db->from('users T1,users T2');
        $this->CI->db->where('T1.id = ' . $doctor_id . ' and T2.id = ' . $patient_id);
        $q = $this->CI->db->get();
        return $q->result_array();
    }
    public function getAll($table, $select = '', $order_fld = '', $order_type = '', $limit = '', $offset = ''){
        if ($order_fld != '' && $order_type != '') {
            $this->CI->db->order_by($order_fld, $order_type);
        }
        if ($select == 'all') {
            $this->CI->db->select('*');
        } else {
            $this->CI->db->select($select);
        }
        if ($limit != '' && $offset != '') {
            $this->CI->db->limit($limit, $offset);
        } else if ($limit != '') {
            $this->CI->db->limit($limit);
        }
        $q = $this->CI->db->get($table);
        // echo $this->CI->db->last_query();die;
        $num_rows = $q->num_rows();
        if ($num_rows > 0) {
            foreach ($q->result_array() as $rows) {
                $data[] = $rows;
            }
            $q->free_result();
            return $data;
        }
    }
    public function getAllwherenew($table, $where, $select = 'all'){
        if ($select == 'all') {
            $this->CI->db->select('*');
        } else {
            $this->CI->db->select($select);
        }
        $this->CI->db->where($where, NULL, FALSE);
        $q        = $this->db->get($table);
        $num_rows = $q->num_rows();
        if ($num_rows > 0) {
            foreach ($q->result() as $rows) {
                $data[] = $rows;
            }
            $q->free_result();
            return $data;
        } else {
            return 'no';
        }
    }
    public function getcount($table, $where){
        $this->CI->db->where($where);
        $q = $this->CI->db->count_all_results($table);
        return $q;
    }
    public function getTotalsum($table, $where, $data){
        $this->CI->db->where($where);
        $this->CI->db->select_sum($data);
        $q = $this->CI->db->get($table);
        return $q->row();
    }
    public function GetJoinRecordNew($table, $field_first, $second_field_join, $tablejointo, $field_second, $tablejointhree, $field_third, $field, $value, $field_val, $where = null){
        $this->CI->db->select("$field_val");
        $this->CI->db->from("$table");
        $this->CI->db->join("$tablejointo", "$tablejointo.$field_second = $table.$field_first");
        if ($tablejointhree && $field_third) {
            $this->CI->db->join("$tablejointhree", "$tablejointhree.$field_third = $table.$second_field_join");
        }
        if (!empty($field) && !empty($value)) {
            $this->CI->db->where("$table.$field", "$value");
        }
        if (!empty($where)) {
            $this->CI->db->where($where);
        }
        if (!empty($group_by)) {
            $this->CI->db->group_by($group_by);
        }
        $q = $this->CI->db->get();

        if ($q->num_rows() > 0) {
            foreach ($q->result() as $rows) {
                $data[] = $rows;
            }
            $q->free_result();
            return $data;
        }
    }
    public function getRecords($table){
        $query = $this->CI->db->get($table);
        return $query->result_array();
    }
    public function getAllRecords($table, $conditions = ''){
        if (!empty($conditions)) {
            $query = $this->CI->db->get_where($table, $conditions);
        } else {
            $query = $this->CI->db->get($table);
        }
        return $query->result_array();
    }
    public function delete($table,$where){
        $this->CI->db->where($where)->delete($table);
    }
    public function update($table, $update, $where){
        $query = $this->CI->db->where($where)->update($table, $update);
    }
    // some extra function start //
    public function countRecord($table, $condition){
        $this->CI->db->where($condition);
        $query = $this->CI->db->get($table);
        return $query->num_rows();
    }
    public function fetchMaxRecord($table, $field){
        $this->CI->db->select_max($field, 'max');
        $query = $this->CI->db->get($table);
        return $query->row_array();
    }
    public function insertPasswordResetString($email_address, $password_reset_key){
        $this->CI->db->where('email', $email_address);
        $this->CI->db->update(USERS, array(
            "password_reset_key" => $password_reset_key
        ));
    }
    public function exists($fields){
        $query = $this->CI->db->get_where(USERS, $fields, 1, 0);
        if ($query->num_rows() == 1)
            return TRUE;
        else
            return FALSE;
    }
    public function updatePassword($password, $password_reset_key){
        $this->CI->db->where('password_reset_key', $password_reset_key);
        $this->CI->db->update(USERS, array(
            "password_reset_key" => "",
            "password" => md5($password)
        ));
    }
    public function check_oldpassword($oldpass, $user_id){
        $this->CI->db->where('id', $user_id);
        $this->CI->db->where('password', md5($oldpass));
        $query = $this->CI->db->get('admins'); //data table
        return $query->num_rows();
    }
    public function insertBatch($table, $data){
        $this->CI->db->insert_batch($table, $data);
        return $this->CI->db->insert_id();
    }
    public function updateBatch($table, $data, $condition){
        $this->CI->db->update_batch($table, $data, $condition);
        return $this->CI->db->insert_id();
    }
    public function find_record($table, $where, $select){
        if (empty($select)) {
            $select = '*';
        }
        $query    = $this->CI->db->query('select ' . $select . ' from users where FIND_IN_SET(' . $where['hospital_id'] . ',hospital_id) and user_role = ' . $where['user_role'] . ' and is_active = 1');
        $num_rows = $query->num_rows();
        if ($num_rows > 0) {
            foreach ($query->result() as $rows) {
                $data[] = $rows;
            }
            $query->free_result();
            return $data;
        }
    }
    
     public function findTopprovider(){
       $SQL = "SELECT users.id,users.username,users.profile_pic,category.name AS service_name FROM users LEFT JOIN provider_service_list ON provider_service_list.provider_id= users.id LEFT JOIN category ON provider_service_list.cat_id=category.id where users.user_role=3 group by category.id";

       $query = $this->CI->db->query($SQL);
       $num_rows = $query->num_rows();

       if ($num_rows > 0) {
           return $query->result();
        } 
    }


    public function getfavourite($userid){
        //$query ="SELECT US.avatarblobid,QP.userid,UF.entityid,QP.title,QP.content,QP.views,QP.postid,QC.title AS categoyname ,(SELECT U.handle FROM qa_users AS U WHERE U.userid=UF.userid) postowner_name,  (SELECT B.content FROM qa_blobs AS B WHERE B.blobid= US.avatarblobid) AS postowner_pic FROM qa_userfavorites AS UF LEFT JOIN qa_posts AS QP ON QP.postid=UF.entityid LEFT JOIN qa_categories AS QC ON QC.categoryid= QP.categoryid LEFT JOIN qa_users as US ON  US.userid= UF.userid WHERE UF.userid='".$where['userid']."' AND UF.entitytype ='Q'";
        $query ="SELECT u.avatarblobid,p.userid,p.postid,p.views,p.title,p.content as post_image,p.created as post_date,p.upvotes,p.downvotes,p.netvotes,p.price,p.pricer,u.handle,c.title as categoyname, b.content as user_image,b.format FROM qa_userfavorites uf LEFT JOIN qa_posts p ON p.postid=uf.entityid LEFT JOIN qa_users u ON p.userid=u.userid LEFT JOIN qa_categories c ON c.categoryid=p.categoryid LEFT JOIN qa_blobs b ON b.blobid=u.avatarblobid WHERE uf.userid=$userid AND uf.entitytype ='Q' AND uf.nouserevents=0 ORDER BY uf.entitytype DESC";
        return $this->CI->db->query($query)->result_array();
                 
    }

    public function getuser($where){
        $query  = "SELECT HEX(passcheck) AS pass from qa_users Where userid=".$where['userid'];
        $result = $this->CI->db->query($query)->result_array();
        return $result;
    }
    public function getAllPostRecord($start_date,$enddate,$todaydate=null,$limit,$offset){
        if(!empty($start_date) AND !empty($enddate))
            $query ="SELECT QP.postid,Qcat.title as categoryname,QP.title AS post_title,QP.content,QP.categoryid,QP.views,QP.upvotes,QP.netvotes FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid WHERE QP.updated BETWEEN '".$start_date."' and '".$enddate."' ORDER BY QP.postid LIMIT ".$limit." OFFSET ".$offset ;
        else {
              $query = "SELECT QP.postid,Qcat.title as categoryname,QP.title AS post_title,QP.content,QP.categoryid,QP.views,QP.upvotes,QP.netvotes FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid WHERE QP.updated = '".$todaydate."'  ORDER BY QP.postid LIMIT ".$limit." OFFSET ".$offset ;
            }    
             $result = $this->CI->db->query($query)->result_array();
            return $result;
        
        
    }

    public function getPostDetailRecord($postid)
    {
        if(!empty($postid)){
            $query ="SELECT QP.postid,Qcat.title as categoryname,QP.title AS post_title,QP.content,QP.categoryid,QP.views,QP.upvotes,QP.netvotes,Qcat.image FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid WHERE QP.postid=$postid";
        }
        return $this->CI->db->query($query)->result_array();        
    }

    public function getallmember($pagination){
        if(!empty($pagination)){
            $query ="SELECT QU.userid, QU.handle, QUP.points, (SELECT COUNT(QUF.userid) FROM qa_userfavorites AS QUF where QUF.userid= QU.userid AND entitytype= 'U') following, (SELECT COUNT(QUF.userid) FROM qa_userfavorites AS QUF WHERE QUF.entityid=QU.userid) followers FROM `qa_userpoints` AS QUP LEFT JOIN qa_users AS QU ON QUP.userid = QU.userid LEFT JOIN qa_posts AS QP ON QP.userid = QU.userid where QP.type='Q' GROUP BY QP.userid ORDER BY QP.userid DESC LIMIT ".$pagination['offset'].",".$pagination['limit'];
        }
        $result = $this->CI->db->query($query)->result_array();
        return $result;
    }    


    public function getMostCommentedPostRecordBackup($start_date,$enddate,$todaydate=null,$limit,$offset,$userid){
        if(!empty($start_date) AND !empty($enddate)){
            $query = "SELECT * FROM `qa_posts` AS A , qa_posts AS B WHERE A.updated BETWEEN '".$start_date."' and '".$enddate."' AND A.postid = B.parentid AND B.type='A' AND A.userid='".$userid."' ORDER BY A.postid DESC LIMIT 0,500";
        }
        else {
            $query = "SELECT * FROM `qa_posts` AS A , qa_posts AS B WHERE A.updated = '".$todaydate."' A.postid = B.parentid AND B.type='A' AND A.userid=20 ORDER BY A.postid DESC LIMIT 0,500";
        }
        //echo $query; die();    
        $result = $this->CI->db->query($query)->result_array();
        return $result;

        //SELECT * FROM `qa_posts` AS A , qa_posts AS B WHERE A.postid = B.parentid AND B.type='A' AND A.userid=20
    }

    public function getMostCommentedPostRecord($start_date,$enddate,$todaydate=null,$limit,$offset,$userid){
        if(!empty($start_date) AND !empty($enddate)){
            $query = "SELECT postid,parentid, COUNT(parentid) as total FROM qa_posts WHERE type='A' AND date(created) BETWEEN date('$start_date') AND date('$enddate') GROUP BY parentid ORDER BY total DESC LIMIT 0,500";
        }
        else {
            $query = "SELECT postid,parentid, COUNT(parentid) as total FROM qa_posts WHERE type='A' AND date(created)=date('$todaydate') GROUP BY parentid ORDER BY total DESC LIMIT 0,500";
        }
        //echo $query; die();    
        $result = $this->CI->db->query($query)->result_array();
        return $result;

        //SELECT * FROM `qa_posts` AS A , qa_posts AS B WHERE A.postid = B.parentid AND B.type='A' AND A.userid=20
    }


    /* Awadhesh Code Here */
    public function getcategorybypost($categoryid,$limit=NULL,$offset=NULL){

        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'5000';

        $query = "SELECT up.points,qa_categories.title as category_name,qa_users.userid,qa_users.credit,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,qa_posts.postid,qa_posts.title,qa_posts.upvotes,qa_posts.downvotes,qa_posts.views,qa_posts.acount,qa_posts.netvotes,qa_posts.content as post_image,qa_posts.created,qa_posts.updated,qa_postmetas.content as post_content,qa_posts.price,qa_posts.pricer FROM qa_posts LEFT JOIN qa_users ON qa_posts.userid=qa_users.userid LEFT JOIN qa_postmetas ON qa_posts.postid = qa_postmetas.postid LEFT JOIN qa_categories ON qa_posts.categoryid = qa_categories.categoryid LEFT JOIN qa_userpoints up ON qa_posts.userid=up.userid WHERE qa_posts.categoryid=$categoryid AND qa_postmetas.title = 'qa_q_extra1' AND qa_posts.parentid IS NULL AND qa_posts.type='Q' AND qa_postmetas.title = 'qa_q_extra1' AND qa_posts.userid!='' ORDER BY qa_posts.postid DESC LIMIT $limit,$offset";
        $result = $this->CI->db->query($query)->result_array();
        return $result;
    }

    public function getAllMostActivePost($start_date,$enddate,$todaydate=null,$limit,$offset){
        if(!empty($start_date) AND !empty($enddate)){
            $query ="SELECT qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,QP.postid,Qcat.title as category_name,QP.title,QP.content as post_image,QP.categoryid,QP.downvotes,QP.views,QP.upvotes,QP.acount,QP.netvotes,qa_postmetas.content as post_content,QP.price,QP.pricer FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid LEFT JOIN qa_users ON QP.userid=qa_users.userid LEFT JOIN qa_postmetas ON QP.postid = qa_postmetas.postid WHERE date(QP.created) BETWEEN date('$start_date') and date('$enddate') AND QP.userid!='' AND QP.categoryid!='' AND QP.parentid IS NULL AND QP.type='Q' AND qa_postmetas.title = 'qa_q_extra1' GROUP BY QP.title ORDER BY QP.views AND QP.netvotes AND qa_postmetas.title = 'qa_q_extra1' DESC LIMIT 0,1000";
        }
        else {
            $query = "SELECT qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,QP.postid,Qcat.title as category_name,QP.title,QP.content as post_image,QP.categoryid,QP.views,QP.upvotes,QP.acount,QP.netvotes,qa_postmetas.content as post_content,QP.price,QP.pricer FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid LEFT JOIN qa_users ON QP.userid=qa_users.userid LEFT JOIN qa_postmetas ON QP.postid = qa_postmetas.postid WHERE date(QP.created) = date('$todaydate') AND QP.userid!='' AND QP.categoryid!='' AND QP.parentid IS NULL AND QP.type='Q' AND qa_postmetas.title = 'qa_q_extra1' GROUP BY QP.title ORDER BY QP.views AND QP.netvotes AND qa_postmetas.title = 'qa_q_extra1' DESC LIMIT 0,1000";
        }
        //echo $query; die();    
        $result = $this->CI->db->query($query)->result_array();
        return $result;
    }

    public function getAllRecentPostRecord($start_date,$enddate,$todaydate=null,$limit,$offset){
        if(!empty($start_date) AND !empty($enddate)){
            $query ="SELECT qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,QP.postid,Qcat.title as category_name,QP.title,QP.content as post_image,QP.categoryid,QP.views,QP.upvotes,QP.acount,QP.netvotes,qa_postmetas.content as post_content,QP.price,QP.pricer FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid LEFT JOIN qa_users ON QP.userid=qa_users.userid LEFT JOIN qa_postmetas ON QP.postid = qa_postmetas.postid WHERE date(QP.created) BETWEEN date('$start_date') and date('$enddate') AND QP.userid!='' AND QP.categoryid!='' AND QP.parentid IS NULL AND QP.type='Q' AND qa_postmetas.title = 'qa_q_extra1' GROUP BY QP.title ORDER BY QP.postid AND qa_postmetas.title = 'qa_q_extra1' DESC LIMIT 0,500";
            //LIMIT ".$limit." OFFSET ".$offset 
        }
        else {
            $query = "SELECT qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,QP.postid,Qcat.title as category_name,QP.title,QP.content as post_image,QP.categoryid,QP.views,QP.upvotes,QP.acount,QP.netvotes,qa_postmetas.content as post_content,QP.price,QP.pricer FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid LEFT JOIN qa_users ON QP.userid=qa_users.userid LEFT JOIN qa_postmetas ON QP.postid = qa_postmetas.postid WHERE date(QP.created)=date('$todaydate') AND QP.userid!='' AND QP.categoryid!='' AND QP.parentid IS NULL AND QP.type='Q' AND qa_postmetas.title = 'qa_q_extra1' GROUP BY QP.title ORDER BY QP.postid AND qa_postmetas.title = 'qa_q_extra1' DESC LIMIT 0,500";
            //LIMIT ".$limit." OFFSET ".$offset;
        } 
        //echo $query; die();   
        $result = $this->CI->db->query($query)->result_array();
        return $result;
    }

    public function getMostVotedPostRecord($start_date,$enddate,$todaydate=null,$limit,$offset){
        if(!empty($start_date) AND !empty($enddate)){
            $query ="SELECT qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,QP.postid,Qcat.title as category_name,QP.title,QP.content as post_image,QP.categoryid,QP.views,QP.upvotes,QP.acount,QP.netvotes,qa_postmetas.content as post_content,QP.price,QP.pricer FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid LEFT JOIN qa_users ON QP.userid=qa_users.userid LEFT JOIN qa_postmetas ON QP.postid = qa_postmetas.postid WHERE date(QP.created) BETWEEN date('$start_date') and date('$enddate') AND QP.userid!='' AND QP.categoryid!='' AND QP.parentid IS NULL AND QP.type='Q' AND qa_postmetas.title = 'qa_q_extra1' GROUP BY QP.title ORDER BY QP.views AND qa_postmetas.title = 'qa_q_extra1' DESC LIMIT 0,500";
        }
        else {
            $query = "SELECT qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,QP.postid,Qcat.title as category_name,QP.title,QP.content as post_image,QP.categoryid,QP.views,QP.upvotes,QP.acount,QP.netvotes,qa_postmetas.content as post_content,QP.price,QP.pricer FROM `qa_posts` AS QP LEFT JOIN qa_postmetas AS QPM ON QP.postid= QPM.postid LEFT JOIN qa_categories AS Qcat ON Qcat.categoryid= QP.categoryid LEFT JOIN qa_users ON QP.userid=qa_users.userid LEFT JOIN qa_postmetas ON QP.postid = qa_postmetas.postid WHERE date(QP.created) = date('$todaydate') AND QP.userid!='' AND QP.categoryid!='' AND QP.parentid IS NULL AND QP.type='Q' AND qa_postmetas.title = 'qa_q_extra1' GROUP BY QP.title ORDER BY QP.views AND qa_postmetas.title = 'qa_q_extra1' DESC LIMIT 0,500";
        }  
        //echo $query; die();  
        $result = $this->CI->db->query($query)->result_array();
        return $result;
    }

    public function countQuery($tbl,$where)
    {
        $this->CI->db->select('count(*) as total');
        $this->CI->db->from($tbl);
        if(!empty($where)){
            $this->CI->db->where($where);
        }
        $query = $this->CI->db->get();
        $cnt = $query->row_array();
        //echo $this->CI->db->last_query(); die();
        return $cnt['total'];
    }

    public function sumQuery($tbl,$sum,$where)
    {
        $this->CI->db->select("sum($sum) as total");
        if(!empty($where)){
            $this->CI->db->where($where);
        }
        $query = $this->CI->db->get($tbl);
        $sm = $query->row_array();
        if($sm>0)
            return number_format($sm['total'],4);
        else
            return 0;

    }

    public function updateQuery($tbl,$data,$where=NULL)
    {       
        if(!empty($where)){
            $this->CI->db->where($where);
        }
        $this->CI->db->update($tbl,$data);
        //$this->CI->db->last_query();
        return 1;
    }

    public function insertQuery($tbl,$data)
    {       
        $this->CI->db->insert($tbl,$data);
        return $this->CI->db->insert_id();
    }

    public function insertQuery1($tbl,$data)
    {       
        $this->CI->db->insert($tbl,$data);
        return 1;
    }

    public function fetchQuery($select,$tbl,$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere=NULL,$orWhere=NULL,$andLike=NULL,$orLike=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL)
    {
        $this->CI->db->select($select);
        $this->CI->db->from($tbl);
        if(!empty($andWhere)){
            $this->CI->db->where($andWhere);
        }
        if(!empty($orWhere)){
            $this->CI->db->where($orWhere);
        }
        if(!empty($andLike)){
            $this->CI->db->like($andLike);
        }
        if(!empty($orLike)){
            $this->CI->db->or_like($orLike);
        }
        if(!empty($groupBy)){
            $this->CI->db->group_by($groupBy);
        }
        if(!empty($orderName)){
            $this->CI->db->order_by($orderName,$ascDsc);
        }
        if(!empty($orderName)){
            $this->CI->db->order_by($orderName,$ascDsc);
        }
        if($eLimit>0){
            $this->CI->db->limit($eLimit,$sLimit);
        }
        if(!empty($joinTbl)){
            $this->CI->db->join($joinTbl, $joinId,$joinLR);
        }
        $query = $this->CI->db->get();
        //echo $this->CI->db->last_query(); die();
        return $query->result_array();
    }

    public function customFetchQuery($select,$tbl,$where='',$order='',$limit='')
    {
        $qry = "SELECT $select FROM $tbl $where $order $limit";

        $query = $this->CI->db->query($qry);
        //echo $this->CI->db->last_query(); die();
        return $query->result_array();        
    }

    public function fetchQueryNew($select,$tbl,$joinTbl=NULL,$joinId=NULL,$joinLR=NULL,$andWhere=NULL,$groupBy=NULL,$orderName=NULL,$ascDsc=NULL,$sLimit=NULL,$eLimit=NULL)
    {
        $this->CI->db->select($select);
        $this->CI->db->from($tbl);
        if(!empty($andWhere)){
            $this->CI->db->where($andWhere);
        }
        if(!empty($groupBy)){
            $this->CI->db->group_by($groupBy);
        }
        if(!empty($orderName)){
            $this->CI->db->order_by($orderName,$ascDsc);
        }
        if(!empty($orderName)){
            $this->CI->db->order_by($orderName,$ascDsc);
        }
        if($eLimit>0){
            $this->CI->db->limit($eLimit,$sLimit);
        }
        if(!empty($joinTbl)){
            $this->CI->db->join($joinTbl, $joinId,$joinLR);
        }
        $query = $this->CI->db->get();
        //echo $this->CI->db->last_query(); die();
        return $query->result_array();
    }

    public function deleteQuery($tbl,$where)
    {       
        if(!empty($where)){
            $this->CI->db->where($where);
        }       
        $this->CI->db->delete($tbl);
        //echo $this->CI->db->last_query(); die();
        if($this->CI->db->affected_rows()>0)
            return 1;
        else
            return 0;
    }

    public function postDataGet($postid,$type='')
    {        
        $type = ($type=='draft')?'DRAFT':'Q';
        $query = "SELECT pm.content as post_content,u.credit,p.postid,p.price,p.pricer,p.categoryid,p.userid,p.upvotes,p.downvotes,p.netvotes,p.views,p.created as post_date,p.title as post_title,p.content as post_image,p.tags,p.price,p.pricer,p.notify,p.userad,p.adimviad,u.email,u.handle as username,up.points,u.avatarblobid,u.coverblobid,b.format,b.content as user_image,c.title as cat_title,c.title as category_name FROM qa_posts p LEFT JOIN qa_users u ON p.userid=u.userid LEFT JOIN qa_blobs b ON u.avatarblobid=b.blobid LEFT JOIN qa_categories c ON p.categoryid=c.categoryid LEFT JOIN qa_userfavorites uf ON p.postid=uf.entityid LEFT JOIN qa_userpoints up ON p.userid=up.userid LEFT JOIN qa_postmetas pm ON p.postid = pm.postid WHERE p.postid=$postid AND p.parentid IS NULL AND p.type='$type' AND p.userid!='' AND pm.title = 'qa_q_extra1' GROUP BY postid";
        //echo $query; die();
        return $this->CI->db->query($query)->row_array();        
    }

    public function getRelatedPost($postid,$categoryid)
    {        
        $query = "SELECT qa_posts.created as post_date,qa_posts.title as post_title,qa_categories.title as category_name,qa_categories.categoryid,qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,qa_posts.postid, qa_posts.title,qa_posts.upvotes,qa_posts.downvotes,qa_posts.views,qa_posts.acount,qa_posts.netvotes,qa_posts.content as post_image,qa_posts.created,qa_posts.updated,qa_posts.tags,qa_posts.price,qa_posts.pricer FROM qa_posts LEFT JOIN qa_users ON qa_posts.userid=qa_users.userid LEFT JOIN qa_categories ON qa_posts.categoryid = qa_categories.categoryid WHERE qa_posts.postid!=$postid AND qa_posts.categoryid=$categoryid AND qa_posts.parentid IS NULL AND qa_posts.type='Q' ORDER BY qa_posts.postid DESC LIMIT 0,100";
        return $this->CI->db->query($query)->result_array();        
    }

    public function postCommentList($postid,$userid='')
    {        
        $query = "SELECT p.categoryid,p.type,p.parentid,p.postid,p.userid,p.netvotes,p.views,p.created as post_date,p.content as comment,p.price,u.handle as username,up.points,u.avatarblobid FROM qa_posts p LEFT JOIN qa_users u ON p.userid=u.userid LEFT JOIN qa_blobs b ON u.avatarblobid=b.blobid LEFT JOIN qa_postmetas pm ON p.postid=pm.postid LEFT JOIN qa_userpoints up ON p.userid=up.userid WHERE p.parentid=$postid AND (p.type='A' OR p.type='A_HIDDEN' AND p.userid=$userid) ORDER BY p.postid ASC";
        //echo $query; die();
        return $this->CI->db->query($query)->result_array();        
    }

    public function postCommentCount($postid,$userid='')
    {        
        $query = "SELECT count(p.postid) as total FROM qa_posts p LEFT JOIN qa_users u ON p.userid=u.userid LEFT JOIN qa_blobs b ON u.avatarblobid=b.blobid LEFT JOIN qa_postmetas pm ON p.postid=pm.postid LEFT JOIN qa_userpoints up ON p.userid=up.userid WHERE p.parentid=$postid AND (p.type='C' OR p.type='C_HIDDEN' AND p.userid=$userid)";

        $query = $this->CI->db->query($query); 
        $cnt = $query->row_array();
        return $cnt['total'];       
    }

    public function postCommentComment($postid,$userid='')
    {        
        $query = "SELECT p.categoryid,p.type,p.parentid,p.postid,p.userid,p.netvotes,p.views,p.created as post_date,p.content as comment,u.handle as username,up.points,u.avatarblobid FROM qa_posts p LEFT JOIN qa_users u ON p.userid=u.userid LEFT JOIN qa_blobs b ON u.avatarblobid=b.blobid LEFT JOIN qa_postmetas pm ON p.postid=pm.postid LEFT JOIN qa_userpoints up ON p.userid=up.userid WHERE p.parentid=$postid AND (p.type='C' OR p.type='C_HIDDEN' AND p.userid=$userid) ORDER BY p.postid ASC";
        //echo $query; die();
        return $this->CI->db->query($query)->result_array();        
    }


    public function getUserFollowing($userid)
    {
        $query ="SELECT p.postid,uf.entityid as userid,u.handle as username,u.avatarblobid,up.points FROM qa_userfavorites uf LEFT JOIN qa_posts p ON p.userid=uf.entityid LEFT JOIN qa_users u ON uf.entityid=u.userid LEFT JOIN qa_userpoints up ON uf.entityid=up.userid WHERE uf.userid=$userid AND uf.entitytype ='U' AND uf.nouserevents=0 AND p.parentid IS NULL AND uf.entityid!=$userid GROUP BY u.userid";
        return $this->CI->db->query($query)->result_array();
    }

    public function getFollowersList($entityid)
    {
        $query ="SELECT uf.userid,u.handle as username,u.avatarblobid,up.points FROM qa_userfavorites uf JOIN qa_users u ON u.userid=uf.userid LEFT JOIN qa_userpoints up ON u.userid=up.userid WHERE uf.entityid=$entityid AND uf.entitytype ='U' AND uf.nouserevents=0 AND uf.userid!=$entityid";
        return $this->CI->db->query($query)->result_array();                 
    }

    public function getPostFollowingUserID($userid,$limit=NULL,$offset=NULL)
    {
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'500';
        $query = "SELECT uf.entityid as userid FROM qa_userfavorites uf WHERE uf.userid=$userid AND uf.entitytype ='U' AND uf.nouserevents=0 LIMIT $limit,$offset";

        return $this->CI->db->query($query)->result_array();                 
    }

    public function getPostFollowing($userid,$limit=NULL,$offset=NULL)
    {
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'500';
        $query = "SELECT p.postid,p.categoryid,p.userid,p.upvotes,p.downvotes,p.netvotes,p.views,p.created as post_date,p.title as post_title,p.content as post_image,p.tags,p.price,p.pricer,u.email,u.handle as username,up.points,u.avatarblobid,u.coverblobid,b.format,b.content as user_image,c.title as cat_title,c.tags as cat_tags,c.qcount,c.position as cat_position,c.image as cat_image FROM qa_posts p LEFT JOIN qa_users u ON p.userid=u.userid LEFT JOIN qa_blobs b ON u.avatarblobid=b.blobid LEFT JOIN qa_categories c ON p.categoryid=c.categoryid LEFT JOIN qa_userpoints up ON p.userid=up.userid WHERE p.userid=$userid AND p.parentid IS NULL AND p.type='Q' GROUP BY p.postid ORDER BY p.userid,p.postid DESC LIMIT $limit,$offset";

        return $this->CI->db->query($query)->result_array();            
    }

    public function getPostByTags($tags,$limit=0,$offset=5000)
    {        
        $query = "SELECT qa_posts.created as post_date,qa_posts.title as post_title,qa_categories.title as category_name,qa_categories.categoryid,qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,qa_posts.postid, qa_posts.title,qa_posts.upvotes,qa_posts.downvotes,qa_posts.views,qa_posts.acount,qa_posts.netvotes,qa_posts.content as post_image,qa_posts.created,qa_posts.updated,qa_posts.tags,qa_posts.price,qa_posts.pricer FROM qa_posts LEFT JOIN qa_users ON qa_posts.userid=qa_users.userid LEFT JOIN qa_categories ON qa_posts.categoryid = qa_categories.categoryid WHERE find_in_set('$tags',qa_posts.tags) AND qa_posts.parentid IS NULL AND qa_posts.postid AND qa_posts.type='Q' ORDER BY qa_posts.postid DESC LIMIT $limit,$offset";
        return $this->CI->db->query($query)->result_array();        
    }


    public function getpublicationofuser($userid,$limit,$offset)
    {        
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'500';
        $query = "SELECT qa_posts.created as post_date,qa_posts.title as post_title,qa_categories.title as category_name,qa_categories.categoryid,qa_users.userid,qa_users.handle,qa_users.uadimageurl,qa_users.avatarblobid,qa_users.avatarwidth,qa_users.avatarheight,qa_posts.postid, qa_posts.title,qa_posts.upvotes,qa_posts.downvotes,qa_posts.views,qa_posts.acount,qa_posts.netvotes,qa_posts.content as post_image,qa_posts.created,qa_posts.updated,qa_posts.tags,qa_posts.price,qa_posts.pricer FROM qa_posts LEFT JOIN qa_users ON qa_posts.userid=qa_users.userid LEFT JOIN qa_categories ON qa_posts.categoryid = qa_categories.categoryid WHERE qa_posts.userid=$userid AND qa_posts.parentid IS NULL AND qa_posts.postid AND qa_posts.type='Q' ORDER BY qa_posts.postid DESC LIMIT $limit,$offset";
        return $this->CI->db->query($query)->result_array();        
    }

    public function memberList($where=NULL,$limit='',$offset='')
    {     
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'5000';   
        $query = "SELECT u.userid,u.email,u.handle as username,up.points,u.avatarblobid,u.coverblobid FROM  qa_users u LEFT JOIN qa_userpoints up ON u.userid=up.userid $where ORDER BY u.handle ASC LIMIT $limit,$offset"; 
        //echo $query; die();

        return $this->CI->db->query($query)->result_array();        
    }

    public function searchRecord($select,$tbl,$where=NULL,$limit='',$offset='')
    {     
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'500';   
        $query = "SELECT $select FROM $tbl $where LIMIT $limit,$offset"; //die();

        return $this->CI->db->query($query)->result_array();        
    }

    public function profileRecentPost($userid)
    {        
        $query = "SELECT b.*,p1.created as post_date,p1.title as post_title,p1.userid,p1.views,p1.netvotes,p1.content as post_image FROM qa_posts p1 JOIN (SELECT DATE_ADD(p.created, INTERVAL 31 DAY) AS expiryDate,p.postid FROM qa_posts p WHERE p.userid=$userid)b ON b.postid=p1.postid WHERE p1.parentid IS NULL AND p1.postid AND p1.type='Q' AND b.expiryDate>NOW() LIMIT 0,5";
        return $this->CI->db->query($query)->result_array();        
    }

    public function wallMessageListBackup($userid,$limit=NULL,$offset=NULL)
    {
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'500';
        $query = "SELECT m.messageid,m.fromuserid,m.content,m.created,u.handle,u.avatarblobid FROM qa_messages m JOIN qa_users u ON m.fromuserid=u.userid WHERE m.fromuserid=$userid AND m.type ='PUBLIC' AND m.touserid=$userid ORDER BY messageid ASC LIMIT $limit,$offset";

        return $this->CI->db->query($query)->result_array();                 
    }

    public function wallMessageList($userid,$limit=NULL,$offset=NULL)
    {
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'500';
        $query = "SELECT m.level,m.touserid,m.messageid,m.fromuserid,m.content,m.created,u.handle,u.avatarblobid FROM qa_messages m JOIN qa_users u ON m.fromuserid=u.userid WHERE m.type ='PUBLIC' AND m.touserid=$userid ORDER BY messageid ASC LIMIT $limit,$offset";
        //echo $query; die();
        return $this->CI->db->query($query)->result_array();                 
    }

    public function followWallMessageList($userid,$login_userid,$limit=NULL,$offset=NULL)
    {
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'500';
        $query = "SELECT m.level,m.touserid,m.messageid,m.fromuserid,m.content,m.created,u.handle,u.avatarblobid FROM qa_messages m JOIN qa_users u ON m.fromuserid=u.userid WHERE m.type ='PUBLIC' AND m.touserid=$userid AND m.fromuserid=$userid ORDER BY messageid ASC LIMIT $limit,$offset";
        //echo $query; die();
        return $this->CI->db->query($query)->result_array();                 
    }

    public function wallMessageReply($messageid)
    {
        $query = "SELECT m.messageid,m.fromuserid,m.content,u.handle,u.avatarblobid FROM qa_messages m JOIN qa_users u ON m.fromuserid=u.userid WHERE m.type ='WREPLY' AND m.level=$messageid ORDER BY messageid ASC";

        return $this->CI->db->query($query)->result_array();                 
    }

    public function getPrivateMsg($fromuserid,$touserid)
    {
        $query = "SELECT m.messageid,m.fromuserid,m.touserid,m.content,m.created,uf.handle as from_user,ut.handle as to_user,uf.avatarblobid as from_user_avatarblobid,ut.avatarblobid as to_user_avatarblobid,uf.userid as from_userid,ut.userid as to_userid FROM qa_messages m LEFT JOIN qa_users uf ON uf.userid=m.fromuserid LEFT JOIN qa_users ut ON ut.userid=m.touserid WHERE (m.fromuserid=$fromuserid AND m.touserid=$touserid) OR (m.fromuserid=$touserid AND m.touserid=$fromuserid) AND m.type='PRIVATE' ORDER BY m.messageid ASC";
        return $this->CI->db->query($query)->result_array();                 
    }

    public function getAllPrivateMsg($login_userid)
    {
        $query = "SELECT m.messageid,m.fromuserid,m.touserid,m.content,m.created,uf.handle as from_user,ut.handle as to_user,uf.avatarblobid as from_user_avatarblobid,ut.avatarblobid as to_user_avatarblobid,uf.userid as from_userid,ut.userid as to_userid FROM qa_messages m INNER JOIN (SELECT least(fromuserid, touserid) AS user_1, greatest(fromuserid, touserid) AS user_2, max(messageid) AS last_id, max(created) as last_timestamp FROM qa_messages GROUP BY least(fromuserid, touserid), greatest(fromuserid, touserid)) s ON least(fromuserid, touserid)=user_1 AND greatest(fromuserid, touserid)=user_2 AND m.messageid=s.last_id LEFT JOIN qa_users uf ON uf.userid=m.fromuserid LEFT JOIN qa_users ut ON ut.userid=m.touserid WHERE m.type='PRIVATE' AND (m.fromuserid=$login_userid OR m.touserid=$login_userid) ORDER BY m.messageid DESC";
        
        //echo $query; die();
        return $this->CI->db->query($query)->result_array();                 
    }

    public function getAllPrivateMsgTo($login_userid)
    {
        $query = "SELECT m.messageid,m.fromuserid,m.touserid,m.content,m.created,uf.handle as from_user,ut.handle as to_user,uf.avatarblobid as from_user_avatarblobid,ut.avatarblobid as to_user_avatarblobid,uf.userid as from_userid,ut.userid as to_userid FROM qa_messages m LEFT JOIN qa_users uf ON uf.userid=m.fromuserid LEFT JOIN qa_users ut ON ut.userid=m.touserid WHERE m.touserid=$login_userid AND m.type='PRIVATE' ORDER BY messageid DESC";
        //echo $query; die();
        return $this->CI->db->query($query)->result_array();                 
    }

    public function getUserProfile($userid)
    {
        $query ="SELECT u.userid,u.handle,u.email,u.paypal,u.avatarblobid,u.coverblobid,u.uadimageurl,u.uadblobid,u.wallposts,u.credit,u.created,up.points,up.qupvotes,up.aupvotes,up.qdownvotes FROM qa_users u LEFT JOIN qa_userpoints up ON up.userid=u.userid WHERE u.userid=$userid";
        return $this->CI->db->query($query)->row_array();                 
    }

    public function getBuyPost($where)
    {
        $query ="SELECT ap.*,u.userid,u.handle FROM qa_adimvipre ap JOIN qa_users u ON ap.buyer=u.userid $where";
        return $this->CI->db->query($query)->result_array();                 
    }


    public function wallNofity($userid,$type='',$limit=NULL,$offset=NULL)
    {
        $limit = ($limit>0)?$limit:'0';   
        $offset = ($offset>0)?$offset:'500';
        $query = "SELECT m.type,m.level,m.touserid,m.messageid,m.fromuserid,m.content,m.created,u.handle,u.avatarblobid FROM qa_messages m JOIN qa_users u ON m.fromuserid=u.userid WHERE m.touserid=$userid AND m.fromuserid!=$userid ORDER BY m.messageid DESC LIMIT $limit,$offset";

        return $this->CI->db->query($query)->result_array();                 
    }

    public function postNotify($user_id)
    {
        $qry = "SELECT b.*,p1.created FROM qa_posts p1 JOIN (SELECT p.postid,p.title,p.parentid FROM qa_posts p WHERE p.userid=$user_id AND p.type='Q')b ON b.postid=p1.parentid AND p1.type='A'";

        return $this->CI->db->query($qry)->result_array();
    }
   
}