<?php
Link of stackoverflow
https://stackoverflow.com/questions/43287288/how-can-i-get-the-last-message-from-each-conversation-in-mysql


=============Controller=========
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
    print_r($data); die();
}








=================model===========
public function getAllPrivateMsg($login_userid)
{
    $query = "SELECT m.messageid,m.fromuserid,m.touserid,m.content,m.created,uf.handle as from_user,ut.handle as to_user,uf.avatarblobid as from_user_avatarblobid,ut.avatarblobid as to_user_avatarblobid,uf.userid as from_userid,ut.userid as to_userid FROM qa_messages m INNER JOIN (SELECT least(fromuserid, touserid) AS user_1, greatest(fromuserid, touserid) AS user_2, max(messageid) AS last_id, max(created) as last_timestamp FROM qa_messages GROUP BY least(fromuserid, touserid), greatest(fromuserid, touserid)) s ON least(fromuserid, touserid)=user_1 AND greatest(fromuserid, touserid)=user_2 AND m.messageid=s.last_id LEFT JOIN qa_users uf ON uf.userid=m.fromuserid LEFT JOIN qa_users ut ON ut.userid=m.touserid WHERE m.type='PRIVATE' AND (m.fromuserid=$login_userid OR m.touserid=$login_userid) ORDER BY m.messageid DESC";
    
    //echo $query; die();
    return $this->CI->db->query($query)->result_array();                 
}