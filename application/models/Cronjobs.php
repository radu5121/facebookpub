<?php
set_time_limit(1500);
defined('BASEPATH') OR exit('Você não tem permissão para acessar o script diretamente!');

class Cronjobs extends CI_Model{

    public function __construct(){
        parent::__construct();
    }

    public function FazerPostagem(){

        $error = false;
        $imagem = false;

        $this->db->where('data_programacao <= ', date('Y-m-d H:i:s'));
        $this->db->where('status', 1);
        $query = $this->db->get('programacoes');

        if($query->num_rows() > 0){

            foreach($query->result() as $result){

                if($result->tipo_programacao == 'texto'){

                    $params['message'] = $result->mensagem_post;

                }elseif($result->tipo_programacao == 'link'){

                    $params['message']     = $result->mensagem_post;
                    $params['link']        = $result->url_link;
                    $params['name']        = $result->titulo_link;
                    $params['description'] = $result->descricao_link;
                    $params['picture']     = $result->imagem_link;

                }elseif($result->tipo_programacao == 'imagem'){

                    $params['message'] = $result->mensagem_post;
                    $params['url']  = $result->imagem_imagem;

                    $imagem = true;

                }elseif($result->tipo_programacao == 'video'){

                    $params['message'] = $result->mensagem_post;
                    $params['name'] = $result->titulo_video;
                    $params['description'] = $result->descricao_video;
                    $params['link']  = $result->link_video;

                }else{

                    $this->db->where('id', $result->id);
                    $this->db->update('programacoes', array('status'=>4));

                    $error = true;
                }

                if(!$error){

                    $this->db->where('id_programacao', $result->id);
                    $paginas = $this->db->get('programacoes_contas');

                    if($paginas->num_rows() > 0){

                        $success = false;

                        foreach($paginas->result() as $pagina){

                            if($pagina->tipo == 'pagina'){

                                $this->db->where('id_page', $pagina->id_conta);
                                $paginasTable = $this->db->get('paginas');

                                if($paginasTable->num_rows() > 0){

                                    $rowPost = $paginasTable->row();

                                    $token = $rowPost->token;
                                }
                            }elseif($pagina->tipo == 'perfil' || $pagina->tipo == 'grupo'){

                                $this->db->where('id', $result->id_user);
                                $user = $this->db->get('usuarios');

                                if($user->num_rows() > 0){

                                    $rowPost = $user->row();

                                    $token = $rowPost->token;
                                }
                            }

                            if(!empty($token)){
                                $post = $this->facebook->FazerPost($pagina->id_conta, $params, $token, $imagem);

                                if(isset($post['id'])){

                                    $separa = explode('_', $post['id']);

                                    $post_id = $separa[count($separa)-1];

                                    $this->db->where('id', $pagina->id);
                                    $this->db->update('programacoes_contas', array('post_id'=>$post_id));

                                    $success = true;
                                }
                            }else{

                                $this->db->where('id', $result->id);
                                $this->db->update('programacoes', array('status'=>4));
                            }
                        }

                        if($success){

                            if($result->repetir_programacao == 1){

                                $ProximaExecucao = date('Y-m-d H:i:s', (strtotime($result->data_programacao) + $result->intervalo));
                                
                                if($ProximaExecucao <= $result->data_final_repeticao){

                                    $this->db->where('id', $result->id);
                                    $this->db->update('programacoes', array('data_programacao'=>$ProximaExecucao));
                                }else{

                                    $this->db->where('id', $result->id);
                                    $this->db->update('programacoes', array('status'=>2));
                                }
                            }else{

                                    $this->db->where('id', $result->id);
                                    $this->db->update('programacoes', array('status'=>2));
                            }
                        }
                    }
                }
            }
        }
    }

    public function VerificarCurtidasPaginas(){

        $this->db->where('status', 1);
        $paginas = $this->db->get('paginas');

        if($paginas->num_rows() > 0){

            foreach($paginas->result() as $pagina){

                $curtidas_inicial = $pagina->curtidas;
                $tokenPage        = $pagina->token;

                $curtidas_atuais  = $this->facebook->getLikesPage($pagina->id_page, $tokenPage);

                if($curtidas_atuais > $curtidas_inicial){

                    $curtidas_ganhas = $curtidas_atuais - $curtidas_inicial;

                    $novas_curtidas = $pagina->crescimento + $curtidas_ganhas;

                    $this->db->where('id', $pagina->id);
                    $this->db->update('paginas', array('curtidas'=>$curtidas_atuais, 'crescimento'=>$novas_curtidas));

                    $this->db->where('id_page', $pagina->id_page);
                    $this->db->where('data', date('Y-m-d'));
                    $relatorio = $this->db->get('relatorio_curtidas');

                    if($relatorio->num_rows() > 0){

                        $row = $relatorio->row();

                        $quantidade = $row->quantidade;

                        $nova_quantidade = $quantidade + $curtidas_ganhas;

                        $this->db->where('id_page', $pagina->id_page);
                        $this->db->where('data', date('Y-m-d'));
                        $this->db->update('relatorio_curtidas', array('quantidade'=>$nova_quantidade));
                    }else{

                        $this->db->insert('relatorio_curtidas', array('id_user'=>$pagina->id_user, 'id_page'=>$pagina->id_page, 'quantidade'=>$curtidas_ganhas, 'data'=>date('Y-m-d')));
                    }
                }
            }
        }
    }

    public function ProprietarioPagina(){

        $this->db->select('p.id_user, u.nome, u.email, u.token');
        $this->db->from('paginas AS p');
        $this->db->join('usuarios AS u', 'p.id_user = u.id', 'inner');
        $this->db->group_by('p.id_user');
        
        $usuariosPaginas = $this->db->get();

        if($usuariosPaginas->num_rows() > 0){

            foreach($usuariosPaginas->result() as $PaginaUser){

                $id_user = $PaginaUser->id_user;
                $token = $PaginaUser->token;
                $nomeUser = $PaginaUser->nome;
                $emailUser = $PaginaUser->email;

                $paginas = array();

                $ListaPaginas = $this->facebook->getPages($token);

                if(!empty($ListaPaginas['data'])){

                    foreach($ListaPaginas['data'] as $itensPagina){

                        $paginas[] = $itensPagina['id'];
                    }

                    $this->db->where_not_in('id_page', $paginas);
                    $queryPaginas = $this->db->get('paginas');

                    if($queryPaginas->num_rows() > 0){

                        foreach($queryPaginas->result() as $paginaEncontrada){

                            $idPage = $paginaEncontrada->id_page;
                            $id     = $paginaEncontrada->id;

                            $html  = 'Olá <b>'.$nomeUser.'</b>, temos uma noticia não tão boa para você :( <br />';
                            $html .= 'Prezamos por ter cada vez mais páginas gerenciadas em nosso sistema, mas infelizmente notamos recentemente que uma das páginas que você gerenciava não está mais em seu poder ou não existe. <br />';
                            $html .= 'A página seria <a href="https://facebook.com/'.$idPage.'" target="_blank">https://facebook.com/'.$idPage.'</a>.Por esse motivo não tivemos outra saida a não ser <b>excluir</b> de nosso sistema, mas fique calmo(a). Quando você estiver gerenciando ela novamente, basta adiciona-lá em nosso sistema. <br />';
                            $html .= '<h3>Porque isso acontece ?</h3><br />';
                            $html .= 'Isso acontece porque não temos permissão para publicar em páginas aonde você não é mais admininistrador. O facebook não deixa nem se quer termos informações sobre essa página a não ser que você seja o administrador. Lamentamos isso. <br /><br />';
                            $html .= '<b>Estaremos a disposição para quaisquer dúvida.</b>';

                            $urlWebmail = parse_url(base_url());

                            $this->email->to($emailUser);
                            $this->email->from('no-reply@'.str_replace('www.', '', $urlWebmail['host']));
                            $this->email->set_mailtype('html');
                            $this->email->subject('Temos uma notícia sobre sua conta');
                            $this->email->message($html);
                            $this->email->send();

                            $this->db->where('id', $id);
                            $this->db->delete('paginas');

                            $this->db->where('id_conta', $idPage);
                            $programacoesContas = $this->db->get('paginas_contas');

                            if($programacoesContas->num_rows() > 0){

                                foreach($programacoesContas->result() as $programacoes){

                                    $idProgramacaoConta = $programacoes->id;
                                    $idProgramacao = $programacoes->id_programacao;

                                    $this->db->where('id', $idProgramacao);
                                    $this->db->delete('programacoes');

                                    $this->db->where('id', $idProgramacaoConta);
                                    $this->db->delete('programacoes_contas');

                                    $this->db->where('id_page', $idPage);
                                    $this->db->delete('relatorio_curtidas');
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
?>