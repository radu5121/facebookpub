<?php
defined('BASEPATH') OR exit('Você não tem permissão para acessar o script diretamente!');

class Conta extends CI_Controller {

    public function __construct(){
        parent::__construct();
    }

    public function index(){

        $data['titulo'] = 'Página inicial';

        $data['jsLoader'] = array(
                                  'assets/examples/js/dashboards/dashboard.v1.js'
                                  );
        
        $this->load->view('conta/templates/header', $data);
        $this->load->view('conta/dashboard/index');
        $this->load->view('conta/templates/footer');
    }

    public function postagem(){

        $data['titulo'] = 'Programar postagem';
        
        $data['jsLoader'] = array(
                                  'assets/vendor/bower_components/jquery-validation/dist/jquery.validate.min.js',
                                  'assets/vendor/bower_components/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js',
                                  'assets/vendor/bower_components/sweetalert/dist/sweetalert.min.js',
                                  'assets/examples/js/demos/form.wizard.js'
                                  );

        $this->load->view('conta/templates/header', $data);
        $this->load->view('conta/postagem/index');
        $this->load->view('conta/templates/footer');
    }

    public function programacoes(){

        $data['titulo'] = 'Programações';

        $this->load->view('conta/templates/header', $data);
        $this->load->view('conta/postagem/programacoes');
        $this->load->view('conta/templates/footer');
    }

    public function paginas(){

        $data['titulo'] = 'Páginas';

        $this->load->view('conta/templates/header', $data);
        $this->load->view('conta/paginas/index');
        $this->load->view('conta/templates/footer');
    }

    public function relatorios(){

        $data['titulo'] = 'Relatórios';

        $data['jsLoader'] = array(
                          'assets/vendor/bower_components/moment/min/moment.min.js',
                          'assets/vendor/bower_components/bootstrap-daterangepicker/daterangepicker.js'
                          );

        $this->load->view('conta/templates/header', $data);
        $this->load->view('conta/relatorios/index');
        $this->load->view('conta/templates/footer');
    }
}
