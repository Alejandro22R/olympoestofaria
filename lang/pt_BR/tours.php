<?php

// Textos de los tutoriales guiados del panel (pasos en App\Domain\Tours\TourCatalog).
return [
    'labels' => [
        'next' => 'Próximo',
        'prev' => 'Voltar',
        'done' => 'Entendi',
        'progress' => '{{current}} de {{total}}',
        'start' => 'Ver tutorial desta tela',
        'reset' => 'Rever tutoriais',
        'reset_help' => 'Os tutoriais de cada tela voltam a abrir sozinhos na próxima visita.',
        'reset_done' => 'Pronto! Os tutoriais vão aparecer de novo.',
    ],
    'errors' => [
        'unknown' => 'Tutorial desconhecido.',
    ],
    'list' => [
        'create' => ['title' => 'Criar um novo', 'body' => 'Use este botão para cadastrar um item novo.'],
        'search' => ['title' => 'Buscar', 'body' => 'Digite um nome, placa ou telefone para encontrar rápido.'],
        'filters' => ['title' => 'Filtrar e ordenar', 'body' => 'Aqui você filtra a lista e escolhe a ordem.'],
        'card' => ['title' => 'Abrir um item', 'body' => 'Cada cartão é um registro. Clique nele para ver os detalhes ou editar.'],
    ],
    'dashboard' => [
        'welcome' => ['title' => 'Bem-vindo ao painel da Olympo!', 'body' => 'Vamos fazer um tour rápido. Você pode rever este tutorial quando quiser no botão “?” lá em cima.'],
        'menu' => ['title' => 'Menu', 'body' => 'Tudo está organizado pelo trabalho do dia: Dia a dia (agenda, quadro e ordens), Clientes e vendas, Oficina e Site.'],
        'actions' => ['title' => 'Acessos rápidos', 'body' => 'Os atalhos para o que você mais faz: agendar, abrir o quadro, montar um orçamento ou cadastrar um cliente.'],
        'today' => ['title' => 'Recepções de hoje', 'body' => 'Os carros que chegam hoje. Quando o cliente chegar, toque em “Receber veículo” para fazer o check-in.'],
        'pending' => ['title' => 'Pendências', 'body' => 'Carros prontos para entregar, ordens atrasadas, solicitações novas e orçamentos esperando o cliente. Clique para ver a lista.'],
        'search' => ['title' => 'Busca rápida', 'body' => 'Procure qualquer cliente, veículo ou ordem por aqui.'],
        'bell' => ['title' => 'Avisos', 'body' => 'Aqui chegam os avisos em tempo real: nova solicitação, pagamento recebido, estoque baixo…'],
        'profile' => ['title' => 'Seu perfil', 'body' => 'Troque seu idioma, sua senha, ative a verificação em duas etapas e escolha quais avisos receber por e-mail.'],
        'help' => ['title' => 'Ajuda em cada tela', 'body' => 'Em qualquer tela, este botão mostra um tutorial de como usá-la.'],
    ],
    'calendar' => [
        'intro' => ['title' => 'Agenda de recepções', 'body' => 'Cada dia mostra quantos carros já estão marcados e quantos ainda cabem.'],
        'new' => ['title' => 'Agendar', 'body' => 'Marque o dia e a hora em que o cliente traz o carro.'],
        'day' => ['title' => 'Os dias', 'body' => 'Clique em um agendamento para ver ou mudar. Os dias cheios ou fechados aparecem marcados.'],
        'list' => ['title' => 'Ver em lista', 'body' => 'Prefere uma lista? Aqui você vê todos os agendamentos com busca e filtros.'],
    ],
    'appointments' => [
        'intro' => ['title' => 'Agendamentos', 'body' => 'Todos os agendamentos em cartões. Abra um para confirmar, remarcar ou fazer o check-in.'],
    ],
    'checkin' => [
        'intro' => ['title' => 'Recepção do veículo (check-in)', 'body' => 'Aqui você registra como o carro chegou. No final o termo é gerado em PDF e enviado ao cliente.'],
        'state' => ['title' => 'Estado geral', 'body' => 'Quilometragem, combustível e objetos que ficaram no carro.'],
        'diagram' => ['title' => 'Avarias', 'body' => 'Toque no desenho do carro onde há um dano, escolha o tipo e, se quiser, tire uma foto.'],
        'consent' => ['title' => 'Consentimento', 'body' => 'Confirme que o cliente autorizou o uso dos dados (LGPD). Não precisa de assinatura.'],
        'finish' => ['title' => 'Concluir', 'body' => 'Ao salvar, o termo é criado e pode ser enviado por WhatsApp ou e-mail.'],
    ],
    'board' => [
        'intro' => ['title' => 'Quadro de produção', 'body' => 'Cada coluna é uma etapa do trabalho e cada cartão é um carro na oficina.'],
        'column' => ['title' => 'Etapas', 'body' => 'O número mostra quantos carros estão nesta etapa.'],
        'card' => ['title' => 'Mover o carro', 'body' => 'Arraste o cartão para a próxima etapa. O cliente acompanha o avanço em tempo real.'],
        'move' => ['title' => 'No celular', 'body' => 'No celular, use esta lista para escolher a nova etapa.'],
    ],
    'inspections' => [
        'intro' => ['title' => 'Recepções', 'body' => 'Todos os check-ins feitos, com as avarias registradas e se o termo já foi enviado.'],
    ],
    'work_orders' => [
        'intro' => ['title' => 'Ordens de serviço', 'body' => 'Cada carro na oficina tem uma ordem com os serviços, materiais, prazo e responsável.'],
        'create' => ['title' => 'Nova ordem', 'body' => 'Normalmente a ordem nasce do check-in ou do orçamento aprovado, mas você pode criar uma aqui.'],
    ],
    'work_order' => [
        'intro' => ['title' => 'A ordem de serviço', 'body' => 'Aqui ficam os serviços, os materiais e o histórico do carro.'],
        'move' => ['title' => 'Mudar de etapa', 'body' => 'Passe o carro para a próxima etapa do quadro.'],
        'progress' => ['title' => 'Registrar avanço', 'body' => 'Adicione fotos e um comentário. Se for visível ao cliente, ele vê na página de acompanhamento.'],
        'deliver' => ['title' => 'Entregar', 'body' => 'Quando estiver pronto, registre a entrega: compara as avarias, cria a garantia e o termo de entrega.'],
    ],
    'projects' => [
        'intro' => ['title' => 'Solicitações do site', 'body' => 'Os projetos que os clientes montam no site chegam aqui. Responda rápido pelo WhatsApp e transforme em orçamento.'],
    ],
    'quotes' => [
        'intro' => ['title' => 'Orçamentos', 'body' => 'Monte o orçamento, envie ao cliente e acompanhe se foi aprovado e se o sinal foi pago.'],
        'create' => ['title' => 'Novo orçamento', 'body' => 'Você também pode criar o orçamento direto de uma solicitação.'],
    ],
    'quote' => [
        'intro' => ['title' => 'Editar o orçamento', 'body' => 'Os totais são calculados sozinhos.'],
        'items' => ['title' => 'Itens', 'body' => 'Adicione os serviços e materiais com quantidade e preço.'],
        'send' => ['title' => 'Enviar', 'body' => 'Envie por WhatsApp ou e-mail. O cliente aprova pelo link, sem senha.'],
        'payment' => ['title' => 'Registrar pagamento', 'body' => 'Se o cliente pagar em dinheiro, Pix direto ou cartão, registre aqui.'],
    ],
    'payments' => [
        'intro' => ['title' => 'Pagamentos', 'body' => 'Todos os sinais e saldos: Pix pelo Mercado Pago e pagamentos registrados à mão.'],
        'create' => ['title' => 'Registrar pagamento', 'body' => 'Os pagamentos são registrados a partir do orçamento ou da ordem.'],
    ],
    'customers' => [
        'intro' => ['title' => 'Clientes', 'body' => 'A ficha de cada cliente com seus veículos, documentos e consentimentos.'],
        'create' => ['title' => 'Novo cliente', 'body' => 'Cadastre o cliente; depois adicione os veículos dentro da ficha.'],
    ],
    'vehicles' => [
        'intro' => ['title' => 'Veículos', 'body' => 'Todos os carros dos clientes, com placa, modelo e quilometragem.'],
    ],
    'inventory' => [
        'intro' => ['title' => 'Estoque', 'body' => 'Couro, linhas, espumas… O estoque baixa sozinho quando uma ordem entra em produção.'],
        'card' => ['title' => 'Repor material', 'body' => 'Os itens com o selo “Repor” estão no mínimo. Abra o item para registrar uma entrada.'],
    ],
    'catalog' => [
        'intro' => ['title' => 'Catálogo', 'body' => 'Os serviços e materiais que aparecem no site e nos orçamentos, com preços e cores.'],
    ],
    'gallery' => [
        'intro' => ['title' => 'Galeria do site', 'body' => 'Fotos e vídeos dos trabalhos que aparecem no site. Escolha onde cada um aparece.'],
    ],
    'reviews' => [
        'intro' => ['title' => 'Avaliações', 'body' => 'O que os clientes acharam. Publique no site só as que eles autorizaram.'],
    ],
    'reports' => [
        'intro' => ['title' => 'Relatórios', 'body' => 'Faturamento, serviços mais vendidos, marcas frequentes, tempo por serviço e rentabilidade.'],
        'period' => ['title' => 'Período', 'body' => 'Escolha o período que quer ver.'],
    ],
    'settings' => [
        'intro' => ['title' => 'Ajustes', 'body' => 'Aqui o dono configura como o sistema funciona, sem precisar de programador.'],
        'sections' => ['title' => 'Seções', 'body' => 'Escolha o que configurar: oficina, vendas, comunicação, equipe e sistema.'],
        'save' => ['title' => 'Salvar', 'body' => 'Depois de mudar, clique em salvar. Toda mudança fica registrada na auditoria.'],
    ],
];
