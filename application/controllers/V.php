<?php


class V extends CI_Controller{

    public function verify(){
        
        $page = "qr_verify";

        if(!file_exists(APPPATH.'views/pages/'.$page.'.php')){
            show_404();
        }

        $data['title'] = "Dashboard"; 

        $data['data'] = $this->Page_model->one_cond_row('profile','docNo',$this->input->get('id'));

        $this->load->view('pages/'.$page, $data);

}

}