<style>
    .error-message {
        color: red;
        font-size: 0.9em;
        margin-top: 5px;
        visibility: hidden;
        /* Oculta a mensagem, mas mantém o espaço reservado */
        height: 1em;
        /* Define uma altura fixa para reservar espaço */
    }

    .error-message.visible {
        visibility: visible;
        /* Torna a mensagem visível */
    }

    .input-error {
        border: 2px solid red;
        outline: none;
    }

    .error-message {
        color: red;
        font-size: 0.8em;
        margin-top: 5px;
        display: block;
    }
</style>



<img src="<?= BASE_URL ?>/img/logo.jpeg" alt="logo" class="header_logo">

<form method="POST" action="<?= BASE_URL ?>user/sendForm">

    <!-- Passo 1: Dados Pessoais -->
    <div class="form-step" id="step-1">
        <fieldset>
            <legend>Dados Pessoais</legend>
            <!-- Conteúdo do Passo 1 -->
            <span class="campo_obrigatorio mb-3">* Indica um campo obrigatório</span>
            <div class="dados_input mb-3">
                <label for="nome"><b>*</b> Nome e sobrenome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="dados_input mb-3">
                <label for="data_nascimento"><b>*</b> Data de Nascimento:</label>
                <input type="text" id="data_nascimento" name="data_nascimento" placeholder="00/00/0000" required>
                <span class="error-message"></span> <!-- Elemento para exibir a mensagem de erro -->
            </div>
            <div class="dados_input mb-3">
                <label for="idade"><b>*</b> Idade:</label>
                <input type="text" id="idade" name="idade" placeholder="Idade" required>
            </div>
            <div class="dados_input mb-3">
                <label for="sexo"><b>*</b> Sexo:</label>
                <select id="sexo" name="sexo" required>
                    <option value="Masculino">Masculino</option>
                    <option value="Feminino">Feminino</option>
                </select>
            </div>
            <div class="dados_input mb-3">
                <label for="cep"><b>*</b> CEP:</label>
                <input type="text" id="cep" name="cep" placeholder="00000-000" required>
                <span class="error-message"></span> <!-- Elemento para a mensagem de erro -->
            </div>
            <div class="dados_input mb-3">
                <label for="endereco"><b>*</b> Logradouro:</label>
                <input type="text" id="endereco" name="endereco" placeholder="Rua" required>
            </div>
            <div class="dados_input mb-3">
                <label for="numero"><b>*</b> Numero:</label>
                <input type="text" id="numero" name="numero" placeholder="N°" required>
            </div>
            <div class="dados_input mb-3">
                <label for="bairro"><b>*</b> Bairro:</label>
                <input type="text" id="bairro" name="bairro" placeholder="Bairro" required>
            </div>
            <div class="dados_input mb-3">
                <label for="cidade"><b>*</b> Cidade:</label>
                <input type="text" id="cidade" name="cidade" placeholder="Cidade" required>
            </div>
            <div class="dados_input mb-3">
                <label for="uf"><b>*</b> Estado:</label>
                <input type="text" id="uf" name="uf" placeholder="UF" required>
            </div>
            <div class="dados_input mb-3">
                <label for="telefone_residencial">* Telefone:</label>
                <input type="text" id="telefone_residencial" name="telefone_residencial" placeholder="(XX) XXXXX-XXXX"
                    required>
                <span class="error-message"></span> <!-- Elemento para a mensagem de erro -->
            </div>
            <div class="dados_input mb-3">
                <label for="estado_civil"><b>*</b> Estado Civil:</label>
                <select id="estado_civil" name="estado_civil" class="form-select" required>
                    <option value="" disabled selected>Selecione</option>
                    <option value="Solteiro(a)">Solteiro(a)</option>
                    <option value="Casado(a)">Casado(a)</option>
                    <option value="Divorciado(a)">Divorciado(a)</option>
                    <option value="Viúvo(a)">Viúvo(a)</option>
                    <option value="União Estável">União Estável</option>
                </select>
            </div>

            <div class="dados_input mb-3">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" placeholder="Email">
                <span class="error-message"></span> <!-- Elemento para exibir a mensagem de erro -->
            </div>
            <div class="dados_input mb-3">
                <label for="profissao">Profissão:</label>
                <input type="text" id="profissao" name="profissao" placeholder="Profissão">
            </div>
            <div class="dados_input mb-3">
                <label for="indicacao">Motivo da visita:</label>
                <input type="text" id="motivo_visita" name="motivo_visita" placeholder="Motivo da visita?">
            </div>
            <div class="dados_input mb-3">
                <label for="indicacao">Indicação:</label>
                <input type="text" id="indicacao" name="indicacao" placeholder="Você foi indicado?">
            </div>
        </fieldset>
        <button type="button" onclick="nextStep(1)">Próximo</button>
    </div>

    <!-- Passo 2: Contato de Emergência -->
    <div class="form-step" id="step-2" style="display: none;">

        <fieldset>
            <legend>Contato de Emergência</legend>
            <!-- Conteúdo do Passo 2 -->
            <div class="dados_input mb- ">
                <label for="nome_emergencia">Nome:</label>
                <input type="text" id="nome_emergencia" name="nome_emergencia" placeholder="Nome">
            </div>
            <div class="dados_input mb-3">
                <label for="telefone_emergencia">Telefone:</label>
                <input type="text" id="telefone_emergencia" name="telefone_emergencia" placeholder="(XX) XXXXX-XXXX">
            </div>
            <div class="dados_input mb-3">
                <label for="medico_emergencia">Médico:</label>
                <input type="text" id="medico_emergencia" name="medico_emergencia" placeholder="Seu médico">
            </div>
            <div class="dados_input mb-3">
                <label for="telefone_medico">Telefone de um médico:</label>
                <input type="text" id="telefone_medico" name="telefone_medico" placeholder="(XX) XXXXX-XXXX">
            </div>
            <div class="dados_input mb-3">
                <label for="convenio_medico">Convênio Médico:</label>
                <input type="text" id="convenio_medico" name="convenio_medico" placeholder="convenio medico">
            </div>
            <div class="dados_input mb-3">
                <label for="cartao_convenio">Cartão do Convênio:</label>
                <input type="text" id="cartao_convenio" name="cartao_convenio" placeholder="cartão do convenio">
            </div>
            <div class="dados_input mb-3">
                <label for="hospital_emergencia">Hospital:</label>
                <input type="text" id="hospital_emergencia" name="hospital_emergencia"
                    placeholder="Hospital de emergencia">
            </div>
            <!-- Adicione outros campos aqui -->
        </fieldset>
        <button type="button" onclick="prevStep(2)">Anterior</button>
        <button type="button" onclick="nextStep(2)">Próximo</button>
    </div>

    <!-- Passo 3: Histórico -->
    <div class="form-step" id="step-3" style="display: none;">

        <fieldset>
            <legend>Histórico</legend>
            <!-- Conteúdo do Passo 3 -->
            <div class="dados_input mb-3">
                <label>* Antecedentes cirúrgicos?</label>
                <div>
                    <input type="radio" id="cirurgico_sim" name="antecedentes_cirurgicos" value="1" required> Sim
                    <input type="radio" id="cirurgico_nao" name="antecedentes_cirurgicos" value="0"> Não
                </div>
                <input type="text" id="detalhes_cirurgicos" name="detalhes_cirurgicos" placeholder="Quais?">
            </div>

            <div class="dados_input mb-3">
                <label>* Tratamento estético anterior?</label>
                <div>
                    <input type="radio" id="trat_estetico_sim" name="tratamento_estetico" value="1" required> Sim
                    <input type="radio" id="trat_estetico_nao" name="tratamento_estetico" value="0"> Não
                </div>
                <input type="text" id="detalhes_tratamento_estetico" name="detalhes_tratamento_estetico"
                    placeholder="Qual?">
            </div>

            <div class="dados_input mb-3">
                <label>* Antecedentes alérgicos?</label>
                <div>
                    <input type="radio" id="alergico_sim" name="antecedentes_alergicos" value="1" required> Sim
                    <input type="radio" id="alergico_nao" name="antecedentes_alergicos" value="0"> Não
                </div>
                <input type="text" id="detalhes_alergia" name="detalhes_alergia" placeholder="Quais?">
            </div>

            <div class="dados_input mb-3">
                <label>* Funcionamento intestinal regular?</label>
                <div>
                    <input type="radio" id="intestinal_sim" name="funcionamento_intestinal" value="1" required> Sim
                    <input type="radio" id="intestinal_nao" name="funcionamento_intestinal" value="0"> Não
                </div>
                <input type="text" id="detalhes_intestinal" name="detalhes_intestinal" placeholder="Observações">
            </div>

            <div class="dados_input mb-3">
                <label>* Pratica atividade física?</label>
                <div>
                    <input type="radio" id="atividade_sim" name="pratica_atividade_fisica" value="1" required> Sim
                    <input type="radio" id="atividade_nao" name="pratica_atividade_fisica" value="0"> Não
                </div>
                <input type="text" id="tipo_atividade" name="tipo_atividade" placeholder="Qual tipo?">
            </div>

            <div class="dados_input mb-3">
                <label>* Alimentação balanceada?</label>
                <div>
                    <input type="radio" id="alimentacao_sim" name="alimentacao_balançada" value="1" required> Sim
                    <input type="radio" id="alimentacao_nao" name="alimentacao_balançada" value="0"> Não
                </div>
                <input type="text" id="tipo_alimentacao_balanceada" name="tipo_alimentacao_balanceada"
                    placeholder="Tipo?">
            </div>

            <div class="dados_input mb-3">
                <label>* Ingere líquidos com frequência?</label>
                <div>
                    <input type="radio" id="liquidos_sim" name="ingere_liquidos" value="1" required> Sim
                    <input type="radio" id="liquidos_nao" name="ingere_liquidos" value="0"> Não
                </div>
                <input type="text" id="quantidade_liquidos" name="quantidade_liquidos" placeholder="Quanto?">
            </div>
            <div class="dados_input mb-3">
                <label>* Tem filhos?</label>
                <div>
                    <input type="radio" id="filhos_sim" name="filhos" value="1" required> Sim
                    <input type="radio" id="filhos_nao" name="filhos" value="0"> Não
                </div>
                <input type="text" id="numero_filhos" name="numero_filhos" placeholder="Quantos?">
            </div>

            <div class="dados_input mb-3">
                <label>* Tem algum problema ortopédico?</label>
                <div>
                    <input type="radio" id="ortopedico_sim" name="problema_ortopedico" value="1" required> Sim
                    <input type="radio" id="ortopedico_nao" name="problema_ortopedico" value="0"> Não
                </div>
                <input type="text" id="detalhes_problema_ortopedico" name="detalhes_problema_ortopedico"
                    placeholder="Qual?">
            </div>

            <div class="dados_input mb-3">
                <label>* Faz algum tratamento médico?</label>
                <div>
                    <input type="radio" id="trat_medico_sim" name="tratamento_medico" value="1" required> Sim
                    <input type="radio" id="trat_medico_nao" name="tratamento_medico" value="0"> Não
                </div>
                <input type="text" id="detalhes_tratamento_medico" name="detalhes_tratamento_medico"
                    placeholder="Qual?">
            </div>

            <div class="dados_input mb-3">
                <label>* Usa ou já usou ácidos na pele?</label>
                <div>
                    <input type="radio" id="acido_sim" name="uso_acido_na_pele" value="1" required> Sim
                    <input type="radio" id="acido_nao" name="uso_acido_na_pele" value="0"> Não
                </div>
                <input type="text" id="detalhes_acido" name="detalhes_acido" placeholder="Qual?">
            </div>

            <div class="dados_input mb-3">
                <label>* Já fez algum tratamento ortomolecular?</label>
                <div>
                    <input type="radio" id="ortomolecular_sim" name="tratamento_ortomolecular" value="1" required> Sim
                    <input type="radio" id="ortomolecular_nao" name="tratamento_ortomolecular" value="0"> Não
                </div>
                <input type="text" id="detalhes_tratamento_ortomolecular" name="detalhes_tratamento_ortomolecular"
                    placeholder="Qual?">
            </div>

            <div class="dados_input mb-3">
                <label>* Portador de Marcapasso?</label>
                <div>
                    <input type="radio" id="marcapasso_sim" name="portador_marcapasso" value="1" required> Sim
                    <input type="radio" id="marcapasso_nao" name="portador_marcapasso" value="0"> Não
                </div>
                <input type="text" id="tipo_portador_marcapasso" name="tipo_portador_marcapasso" placeholder="Qual?">
            </div>

            <div class="dados_input mb-3">
                <label>* Presença de metais no corpo?</label>
                <div>
                    <input type="radio" id="metais_sim" name="metais_presentes" value="1" required> Sim
                    <input type="radio" id="metais_nao" name="metais_presentes" value="0"> Não
                </div>
                <input type="text" id="local_metais" name="local_metais" placeholder="Local?">
            </div>

            <div class="dados_input mb-3">
                <label>* Antecedentes oncológicos?</label>
                <div>
                    <input type="radio" id="oncologico_sim" name="antecedentes_oncologicos" value="1" required> Sim
                    <input type="radio" id="oncologico_nao" name="antecedentes_oncologicos" value="0"> Não
                </div>
                <input type="text" id="detalhes_oncologicos" name="detalhes_oncologicos" placeholder="Quais?">
            </div>

            <div class="dados_input mb-3">
                <label>* Ciclo menstrual regular?</label>
                <div>
                    <input type="radio" id="ciclo_sim" name="ciclo_menstrual_regular" value="1" required> Sim
                    <input type="radio" id="ciclo_nao" name="ciclo_menstrual_regular" value="0"> Não
                </div>
                <input type="text" id="observacao_ciclo" name="observacao_ciclo" placeholder="Observações">
            </div>

            <div class="dados_input mb-3">
                <label>* Possui varizes?</label>
                <div>
                    <input type="radio" id="varizes_sim" name="varizes" value="1" required> Sim
                    <input type="radio" id="varizes_nao" name="varizes" value="0"> Não
                </div>
                <input type="text" id="grau_varizes" name="grau_varizes" placeholder="Grau?">
            </div>

            <div class="dados_input mb-3">
                <label>* Possui corte ou machucado?</label>
                <div>
                    <input type="radio" id="lesoes_sim" name="lesoes" value="1" required> Sim
                    <input type="radio" id="lesoes_nao" name="lesoes" value="0"> Não
                </div>
                <input type="text" id="detalhes_lesoes" name="detalhes_lesoes" placeholder="Detalhes?">
            </div>
            <div class="dados_input mb-3">
                <label>Cuidados Diários e produtos em uso:</label>
                <input type="text" id="cuidados_diarios" name="cuidados_diarios" placeholder="Descreva..."></input>
            </div>
            <div class="row">
                <div class="dados_input mb-3">
                    <label>* Costuma permanecer muito tempo sentada?</label>
                    <div>
                        <input type="radio" id="permanece_sim" name="permanece_sentado" value="1" required> Sim
                        <input type="radio" id="permanece_nao" name="permanece_sentado" value="0"> Não
                    </div>
                </div>
                <div class="dados_input mb-3">
                    <label>* Usa método anticoncepcional?</label>
                    <div>
                        <input type="radio" id="anticoncepcional_sim" name="metodo_anticoncepcional" value="1" required>
                        Sim
                        <input type="radio" id="anticoncepcional_nao" name="metodo_anticoncepcional" value="0"> Não
                    </div>
                </div>
                <div class="dados_input mb-3">
                    <label>* É gestante?</label>
                    <div>
                        <input type="radio" id="gestante_sim" name="gestante" value="1" required> Sim
                        <input type="radio" id="gestante_nao" name="gestante" value="0"> Não
                    </div>
                </div>
                <div class="dados_input mb-3">
                    <label>* É fumante?</label>
                    <div>
                        <input type="radio" id="fumante_sim" name="fumante" value="1" required> Sim
                        <input type="radio" id="fumante_nao" name="fumante" value="0"> Não
                    </div>
                </div>
                <div class="dados_input mb-3">
                    <label>* Possui hipertensão?</label>
                    <div>
                        <input type="radio" id="hipertensao_sim" name="hipertensao" value="1" required> Sim
                        <input type="radio" id="hipertensao_nao" name="hipertensao" value="0"> Não
                    </div>
                </div>
                <div class="dados_input mb-3">
                    <label>* Possui hipotensão?</label>
                    <div>
                        <input type="radio" id="hipotensao_sim" name="hipotensao" value="1" required> Sim
                        <input type="radio" id="hipotensao_nao" name="hipotensao" value="0"> Não
                    </div>
                </div>
                <div class="dados_input mb-3">
                    <label>* Possui diabetes?</label>
                    <div>
                        <input type="radio" id="diabetes_sim" name="diabetes" value="1" required> Sim
                        <input type="radio" id="diabetes_nao" name="diabetes" value="0"> Não
                    </div>
                </div>
                <div class="dados_input mb-3">
                    <label>* Possui epilepsia?</label>
                    <div>
                        <input type="radio" id="epilepsia_sim" name="epilepsia" value="1" required> Sim
                        <input type="radio" id="epilepsia_nao" name="epilepsia" value="0"> Não
                    </div>
                </div>

            </div>
            <!-- Adicione outros campos aqui -->
        </fieldset>
        <fieldset>
            <legend>Termo de Responsabilidade</legend>
            <label>Estou ciente e de acordo com todas as informações acima relacionadas:</label>

            <label for="assinatura_cliente">Assinatura do Cliente:</label>
            <textarea id="assinatura_cliente" name="assinatura_cliente"
                placeholder="Digite seu nome completo"></textarea>
            <span class="error-message"></span> <!-- Elemento para exibir a mensagem de erro -->
        </fieldset>
        <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin_id) ?>">
        <button type="button" onclick="prevStep(3)">Anterior</button>

        <button type="submit">Enviar</button>
    </div>

</form>
<!-- <script src="<?= BASE_URL ?>assets/js/core/bootstrap.min.js"></script> -->


<script>
    const idade = document.getElementById('idade');
    const numero_filhos = document.getElementById('numero_filhos');
    const numero = document.getElementById('numero');

    if (!isNaN(idade.value) && idade.value < 0) {
        idade.value = '';
    }
    if (!isNaN(numero_filhos.value) && numero_filhos.value < 0) {
        numero_filhos.value = '';
    }
    if (!isNaN(numero.value) && numero.value < 0) {
        numero.value = '';
    }
</script>