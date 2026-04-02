import { getBaseUrl } from '../shared/api.js';

export function createPerfilContext(mode = 'perfil') {
    const BASE = getBaseUrl();

    return {
        mode,
        BASE,
        API: `${BASE}api/`,
        form: document.getElementById('profileForm'),
        fields: {
            nome: document.getElementById('nome'),
            email: document.getElementById('email'),
            cpf: document.getElementById('cpf'),
            dataNascimento: document.getElementById('data_nascimento'),
            telefone: document.getElementById('telefone'),
            sexo: document.getElementById('sexo'),
            cep: document.getElementById('end_cep'),
            rua: document.getElementById('end_rua'),
            numero: document.getElementById('end_numero'),
            complemento: document.getElementById('end_complemento'),
            bairro: document.getElementById('end_bairro'),
            cidade: document.getElementById('end_cidade'),
            estado: document.getElementById('end_estado'),
        },
        avatar: {
            image: document.getElementById('avatarImg'),
            initials: document.getElementById('avatarInitials'),
            editButton: document.getElementById('avatarEditBtn'),
            input: document.getElementById('avatarInput'),
        },
        emailPendingNoticeId: 'email-pending-notice',
    };
}
