import crypto from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const packageArgument = process.argv.find( ( argument ) => argument.startsWith( '--package=' ) );
const packageRoot = packageArgument ? path.resolve( packageArgument.slice( '--package='.length ) ) : root;
const languagesDirectory = path.join( packageRoot, 'languages' );
const potPath = path.join( languagesDirectory, 'cart-relay.pot' );
const checkOnly = process.argv.includes( '--check' );
const scriptHandle = 'cart-relay-front-js';

const locales = {
	es_CO: {
		languageName: 'Spanish (Colombia)',
		team: 'Spanish (Colombia)',
		pluralForms: 'nplurals=2; plural=(n != 1);',
		translationIndex: 1,
	},
	es_ES: {
		languageName: 'Spanish',
		team: 'Spanish',
		pluralForms: 'nplurals=2; plural=(n != 1);',
		translationIndex: 1,
	},
	fr_FR: {
		languageName: 'French',
		team: 'French',
		pluralForms: 'nplurals=2; plural=(n > 1);',
		translationIndex: 2,
	},
	pt_BR: {
		languageName: 'Portuguese (Brazil)',
		team: 'Portuguese (Brazil)',
		pluralForms: 'nplurals=2; plural=(n > 1);',
		translationIndex: 3,
	},
	it_IT: {
		languageName: 'Italian',
		team: 'Italian',
		pluralForms: 'nplurals=2; plural=(n != 1);',
		translationIndex: 4,
	},
};

// Source, Spanish, French, Brazilian Portuguese, Italian.
const singularRows = [
	[ "Cart Relay for WooCommerce", "Cart Relay para WooCommerce", "Cart Relay pour WooCommerce", "Cart Relay para WooCommerce", "Cart Relay per WooCommerce" ],
	[ "Import and export WooCommerce carts with simple CSV files using SKU and quantity.", "Importa y exporta carritos de WooCommerce con sencillos archivos CSV mediante SKU y cantidad.", "Importez et exportez des paniers WooCommerce avec de simples fichiers CSV utilisant l’UGS et la quantité.", "Importe e exporte carrinhos do WooCommerce com arquivos CSV simples usando SKU e quantidade.", "Importa ed esporta i carrelli WooCommerce con semplici file CSV usando SKU e quantità." ],
	[ "Gilbert Rodríguez", "Gilbert Rodríguez", "Gilbert Rodríguez", "Gilbert Rodríguez", "Gilbert Rodríguez" ],
	[ "https://gilbertrdz.dev", "https://gilbertrdz.dev", "https://gilbertrdz.dev", "https://gilbertrdz.dev", "https://gilbertrdz.dev" ],
	[ "Cart Relay settings", "Ajustes de Cart Relay", "Réglages de Cart Relay", "Configurações do Cart Relay", "Impostazioni di Cart Relay" ],
	[ "Cart Relay", "Cart Relay", "Cart Relay", "Cart Relay", "Cart Relay" ],
	[ "You are not allowed to manage these settings.", "No tienes permisos para gestionar estos ajustes.", "Vous n’avez pas l’autorisation de gérer ces réglages.", "Você não tem permissão para gerenciar estas configurações.", "Non hai l’autorizzazione per gestire queste impostazioni." ],
	[ "The request could not be verified. Refresh the page and try again.", "No se ha podido verificar la solicitud. Actualiza la página e inténtalo de nuevo.", "La requête n’a pas pu être vérifiée. Actualisez la page et réessayez.", "Não foi possível verificar a solicitação. Atualize a página e tente novamente.", "Non è stato possibile verificare la richiesta. Aggiorna la pagina e riprova." ],
	[ "Review the highlighted fields and try again.", "Revisa los campos resaltados e inténtalo de nuevo.", "Vérifiez les champs mis en évidence et réessayez.", "Revise os campos destacados e tente novamente.", "Controlla i campi evidenziati e riprova." ],
	[ "Settings saved successfully.", "Los ajustes se han guardado correctamente.", "Les réglages ont bien été enregistrés.", "As configurações foram salvas com sucesso.", "Le impostazioni sono state salvate correttamente." ],
	[ "Cart features", "Funciones del carrito", "Fonctionnalités du panier", "Recursos do carrinho", "Funzionalità del carrello" ],
	[ "Choose which cart tools are available to customers.", "Elige qué herramientas del carrito están disponibles para los clientes.", "Choisissez les outils de panier disponibles pour les clients.", "Escolha quais ferramentas do carrinho ficam disponíveis para os clientes.", "Scegli quali strumenti del carrello rendere disponibili ai clienti." ],
	[ "Features", "Funciones", "Fonctionnalités", "Recursos", "Funzionalità" ],
	[ "Enable cart export", "Activar la exportación del carrito", "Activer l’exportation du panier", "Ativar exportação do carrinho", "Abilita l’esportazione del carrello" ],
	[ "Allow customers to download the current cart as a CSV file.", "Permite a los clientes descargar el carrito actual como archivo CSV.", "Permettez aux clients de télécharger le panier actuel au format CSV.", "Permita que os clientes baixem o carrinho atual como um arquivo CSV.", "Consenti ai clienti di scaricare il carrello attuale come file CSV." ],
	[ "Enable cart import", "Activar la importación del carrito", "Activer l’importation du panier", "Ativar importação do carrinho", "Abilita l’importazione del carrello" ],
	[ "Allow customers to add products from a Cart Relay CSV file.", "Permite a los clientes añadir productos desde un archivo CSV de Cart Relay.", "Permettez aux clients d’ajouter des produits à partir d’un fichier CSV Cart Relay.", "Permita que os clientes adicionem produtos de um arquivo CSV do Cart Relay.", "Consenti ai clienti di aggiungere prodotti da un file CSV di Cart Relay." ],
	[ "Require an account", "Requerir una cuenta", "Exiger un compte", "Exigir uma conta", "Richiedi un account" ],
	[ "Limit import and export actions to logged-in customers.", "Limita las acciones de importación y exportación a los clientes conectados.", "Limitez les actions d’importation et d’exportation aux clients connectés.", "Limite as ações de importação e exportação aos clientes conectados.", "Limita le azioni di importazione ed esportazione ai clienti connessi." ],
	[ "Labels and behavior", "Etiquetas y comportamiento", "Libellés et comportement", "Rótulos e comportamento", "Etichette e comportamento" ],
	[ "Customize labels and control how imported carts are applied.", "Personaliza las etiquetas y controla cómo se aplican los carritos importados.", "Personnalisez les libellés et contrôlez la façon dont les paniers importés sont appliqués.", "Personalize os rótulos e controle como os carrinhos importados são aplicados.", "Personalizza le etichette e controlla come vengono applicati i carrelli importati." ],
	[ "Display & behavior", "Visualización y comportamiento", "Affichage et comportement", "Exibição e comportamento", "Visualizzazione e comportamento" ],
	[ "Export button text", "Texto del botón de exportación", "Texte du bouton d’exportation", "Texto do botão de exportação", "Testo del pulsante di esportazione" ],
	[ "Text displayed on the cart export button.", "Texto que se muestra en el botón de exportación del carrito.", "Texte affiché sur le bouton d’exportation du panier.", "Texto exibido no botão de exportação do carrinho.", "Testo visualizzato sul pulsante di esportazione del carrello." ],
	[ "Export cart", "Exportar carrito", "Exporter le panier", "Exportar carrinho", "Esporta carrello" ],
	[ "Import button text", "Texto del botón de importación", "Texte du bouton d’importation", "Texto do botão de importação", "Testo del pulsante di importazione" ],
	[ "Text displayed on the cart import button.", "Texto que se muestra en el botón de importación del carrito.", "Texte affiché sur le bouton d’importation du panier.", "Texto exibido no botão de importação do carrinho.", "Testo visualizzato sul pulsante di importazione del carrello." ],
	[ "Import cart", "Importar carrito", "Importer le panier", "Importar carrinho", "Importa carrello" ],
	[ "Import mode", "Modo de importación", "Mode d’importation", "Modo de importação", "Modalità di importazione" ],
	[ "Merge with the current cart or replace its contents.", "Combina con el carrito actual o reemplaza su contenido.", "Fusionnez avec le panier actuel ou remplacez son contenu.", "Mescle com o carrinho atual ou substitua seu conteúdo.", "Unisci al carrello attuale o sostituiscine il contenuto." ],
	[ "Merge with current cart", "Combinar con el carrito actual", "Fusionner avec le panier actuel", "Mesclar com o carrinho atual", "Unisci al carrello attuale" ],
	[ "Replace current cart", "Reemplazar el carrito actual", "Remplacer le panier actuel", "Substituir o carrinho atual", "Sostituisci il carrello attuale" ],
	[ "Cart controls location", "Ubicación de los controles del carrito", "Emplacement des commandes du panier", "Localização dos controles do carrinho", "Posizione dei controlli del carrello" ],
	[ "Choose where the import and export controls appear on the classic cart page.", "Elige dónde aparecen los controles de importación y exportación en la página clásica del carrito.", "Choisissez où les commandes d’importation et d’exportation apparaissent sur la page classique du panier.", "Escolha onde os controles de importação e exportação aparecem na página clássica do carrinho.", "Scegli dove mostrare i controlli di importazione ed esportazione nella pagina classica del carrello." ],
	[ "Before the cart table", "Antes de la tabla del carrito", "Avant le tableau du panier", "Antes da tabela do carrinho", "Prima della tabella del carrello" ],
	[ "After the cart table", "Después de la tabla del carrito", "Après le tableau du panier", "Depois da tabela do carrinho", "Dopo la tabella del carrello" ],
	[ "After the cart totals", "Después de los totales del carrito", "Après les totaux du panier", "Depois dos totais do carrinho", "Dopo i totali del carrello" ],
	[ "Invalid export request.", "Solicitud de exportación no válida.", "Requête d’exportation non valide.", "Solicitação de exportação inválida.", "Richiesta di esportazione non valida." ],
	[ "WooCommerce cart is not available.", "El carrito de WooCommerce no está disponible.", "Le panier WooCommerce n’est pas disponible.", "O carrinho do WooCommerce não está disponível.", "Il carrello WooCommerce non è disponibile." ],
	[ "Invalid request.", "Solicitud no válida.", "Requête non valide.", "Solicitação inválida.", "Richiesta non valida." ],
	[ "The CSV is empty or has no importable rows.", "El CSV está vacío o no contiene filas que se puedan importar.", "Le fichier CSV est vide ou ne contient aucune ligne importable.", "O CSV está vazio ou não contém linhas que possam ser importadas.", "Il CSV è vuoto o non contiene righe importabili." ],
	[ "Product not found.", "Producto no encontrado.", "Produit introuvable.", "Produto não encontrado.", "Prodotto non trovato." ],
	[ "The import chunk information is invalid.", "La información del bloque de importación no es válida.", "Les informations du lot d’importation ne sont pas valides.", "As informações do lote de importação são inválidas.", "Le informazioni sul blocco di importazione non sono valide." ],
	[ "There are no products to import in this chunk.", "No hay productos que importar en este bloque.", "Il n’y a aucun produit à importer dans ce lot.", "Não há produtos para importar neste lote.", "Non ci sono prodotti da importare in questo blocco." ],
	[ "Invalid template request.", "Solicitud de plantilla no válida.", "Requête de modèle non valide.", "Solicitação de modelo inválida.", "Richiesta di modello non valida." ],
	[ "You must select a CSV file.", "Debes seleccionar un archivo CSV.", "Vous devez sélectionner un fichier CSV.", "Você deve selecionar um arquivo CSV.", "Devi selezionare un file CSV." ],
	[ "The CSV file could not be uploaded.", "No se ha podido subir el archivo CSV.", "Le fichier CSV n’a pas pu être téléversé.", "Não foi possível enviar o arquivo CSV.", "Non è stato possibile caricare il file CSV." ],
	[ "The CSV file is not valid.", "El archivo CSV no es válido.", "Le fichier CSV n’est pas valide.", "O arquivo CSV não é válido.", "Il file CSV non è valido." ],
	[ "The CSV file is empty.", "El archivo CSV está vacío.", "Le fichier CSV est vide.", "O arquivo CSV está vazio.", "Il file CSV è vuoto." ],
	[ "The CSV file cannot exceed 2 MB.", "El archivo CSV no puede superar los 2 MB.", "Le fichier CSV ne peut pas dépasser 2 Mo.", "O arquivo CSV não pode exceder 2 MB.", "Il file CSV non può superare i 2 MB." ],
	[ "The file must use the .csv extension.", "El archivo debe usar la extensión .csv.", "Le fichier doit utiliser l’extension .csv.", "O arquivo deve usar a extensão .csv.", "Il file deve avere l’estensione .csv." ],
	[ "The uploaded file does not contain valid CSV data.", "El archivo subido no contiene datos CSV válidos.", "Le fichier téléversé ne contient pas de données CSV valides.", "O arquivo enviado não contém dados CSV válidos.", "Il file caricato non contiene dati CSV validi." ],
	[ "The CSV file could not be read.", "No se ha podido leer el archivo CSV.", "Le fichier CSV n’a pas pu être lu.", "Não foi possível ler o arquivo CSV.", "Non è stato possibile leggere il file CSV." ],
	[ "The import chunk is too large.", "El bloque de importación es demasiado grande.", "Le lot d’importation est trop volumineux.", "O lote de importação é muito grande.", "Il blocco di importazione è troppo grande." ],
	[ "An import request cannot contain more than %d products.", "Una solicitud de importación no puede contener más de %d productos.", "Une requête d’importation ne peut pas contenir plus de %d produits.", "Uma solicitação de importação não pode conter mais de %d produtos.", "Una richiesta di importazione non può contenere più di %d prodotti." ],
	[ "Product %d could not be added to the cart.", "No se ha podido añadir el producto %d al carrito.", "Le produit %d n’a pas pu être ajouté au panier.", "Não foi possível adicionar o produto %d ao carrinho.", "Non è stato possibile aggiungere il prodotto %d al carrello." ],
	[ "No valid products were found in the CSV.", "No se han encontrado productos válidos en el CSV.", "Aucun produit valide n’a été trouvé dans le CSV.", "Nenhum produto válido foi encontrado no CSV.", "Nel CSV non sono stati trovati prodotti validi." ],
	[ "Unknown error.", "Error desconocido.", "Erreur inconnue.", "Erro desconhecido.", "Errore sconosciuto." ],
	[ "Row %1$d: %2$s", "Fila %1$d: %2$s", "Ligne %1$d : %2$s", "Linha %1$d: %2$s", "Riga %1$d: %2$s" ],
	[ "%s is required.", "%s es obligatorio.", "%s est obligatoire.", "%s é obrigatório.", "%s è obbligatorio." ],
	[ "%s must be a valid email address.", "%s debe ser una dirección de correo electrónico válida.", "%s doit être une adresse e-mail valide.", "%s deve ser um endereço de e-mail válido.", "%s deve essere un indirizzo email valido." ],
	[ "%s must be a valid URL.", "%s debe ser una URL válida.", "%s doit être une URL valide.", "%s deve ser uma URL válida.", "%s deve essere un URL valido." ],
	[ "%s must be numeric.", "%s debe ser numérico.", "%s doit être numérique.", "%s deve ser numérico.", "%s deve essere un valore numerico." ],
	[ "%s must be an integer.", "%s debe ser un número entero.", "%s doit être un nombre entier.", "%s deve ser um número inteiro.", "%s deve essere un numero intero." ],
	[ "%1$s must be at least %2$s.", "%1$s debe ser al menos %2$s.", "%1$s doit être au moins égal à %2$s.", "%1$s deve ser pelo menos %2$s.", "%1$s deve essere almeno %2$s." ],
	[ "%1$s must not exceed %2$s.", "%1$s no debe superar %2$s.", "%1$s ne doit pas dépasser %2$s.", "%1$s não deve exceder %2$s.", "%1$s non deve superare %2$s." ],
	[ "%1$s must contain at least %2$s characters.", "%1$s debe contener al menos %2$s caracteres.", "%1$s doit contenir au moins %2$s caractères.", "%1$s deve conter pelo menos %2$s caracteres.", "%1$s deve contenere almeno %2$s caratteri." ],
	[ "%1$s must not exceed %2$s characters.", "%1$s no debe superar los %2$s caracteres.", "%1$s ne doit pas dépasser %2$s caractères.", "%1$s não deve exceder %2$s caracteres.", "%1$s non deve superare %2$s caratteri." ],
	[ "%s contains an invalid selection.", "%s contiene una selección no válida.", "%s contient une sélection non valide.", "%s contém uma seleção inválida.", "%s contiene una selezione non valida." ],
	[ "%s must be enabled or disabled.", "%s debe estar activado o desactivado.", "%s doit être activé ou désactivé.", "%s deve estar ativado ou desativado.", "%s deve essere abilitato o disabilitato." ],
	[ "%s is invalid.", "%s no es válido.", "%s n’est pas valide.", "%s é inválido.", "%s non è valido." ],
	[ "Cart Relay requires WooCommerce to be installed and active in order to import/export carts.", "Cart Relay requiere que WooCommerce esté instalado y activo para importar y exportar carritos.", "Cart Relay nécessite que WooCommerce soit installé et activé pour importer ou exporter des paniers.", "O Cart Relay requer que o WooCommerce esteja instalado e ativo para importar ou exportar carrinhos.", "Cart Relay richiede che WooCommerce sia installato e attivo per importare o esportare i carrelli." ],
	[ "The CSV cannot contain more than %d product rows.", "El CSV no puede contener más de %d filas de productos.", "Le CSV ne peut pas contenir plus de %d lignes de produits.", "O CSV não pode conter mais de %d linhas de produtos.", "Il CSV non può contenere più di %d righe di prodotti." ],
	[ "The CSV contains the duplicate column \"%s\".", "El CSV contiene la columna duplicada \"%s\".", "Le CSV contient la colonne en double « %s ».", "O CSV contém a coluna duplicada \"%s\".", "Il CSV contiene la colonna duplicata \"%s\"." ],
	[ "The CSV header row is empty.", "La fila de encabezado del CSV está vacía.", "La ligne d’en-tête du CSV est vide.", "A linha de cabeçalho do CSV está vazia.", "La riga di intestazione del CSV è vuota." ],
	[ "The CSV must include a quantity column.", "El CSV debe incluir una columna quantity.", "Le CSV doit inclure une colonne quantity.", "O CSV deve incluir uma coluna quantity.", "Il CSV deve includere una colonna quantity." ],
	[ "The CSV must include product_id, variation_id, or sku.", "El CSV debe incluir product_id, variation_id o sku.", "Le CSV doit inclure product_id, variation_id ou sku.", "O CSV deve incluir product_id, variation_id ou sku.", "Il CSV deve includere product_id, variation_id o sku." ],
	[ "The CSV cannot contain more than %d columns.", "El CSV no puede contener más de %d columnas.", "Le CSV ne peut pas contenir plus de %d colonnes.", "O CSV não pode conter mais de %d colunas.", "Il CSV non può contenere più di %d colonne." ],
	[ "CSV values cannot exceed %d bytes.", "Los valores del CSV no pueden superar los %d bytes.", "Les valeurs du CSV ne peuvent pas dépasser %d octets.", "Os valores do CSV não podem exceder %d bytes.", "I valori CSV non possono superare %d byte." ],
	[ "Variation not found for ID %d.", "No se ha encontrado la variación con ID %d.", "Variation introuvable pour l’ID %d.", "Variação não encontrada para o ID %d.", "Variazione non trovata per l’ID %d." ],
	[ "ID %d does not match a variation.", "El ID %d no corresponde a una variación.", "L’ID %d ne correspond pas à une variation.", "O ID %d não corresponde a uma variação.", "L’ID %d non corrisponde a una variazione." ],
	[ "Variation %1$d does not belong to product %2$d.", "La variación %1$d no pertenece al producto %2$d.", "La variation %1$d n’appartient pas au produit %2$d.", "A variação %1$d não pertence ao produto %2$d.", "La variazione %1$d non appartiene al prodotto %2$d." ],
	[ "Product not found for ID %d.", "No se ha encontrado el producto con ID %d.", "Produit introuvable pour l’ID %d.", "Produto não encontrado para o ID %d.", "Prodotto non trovato per l’ID %d." ],
	[ "Product not found for SKU %s.", "No se ha encontrado el producto con SKU %s.", "Produit introuvable pour l’UGS %s.", "Produto não encontrado para o SKU %s.", "Prodotto non trovato per lo SKU %s." ],
	[ "The row must include product_id, variation_id, or sku.", "La fila debe incluir product_id, variation_id o sku.", "La ligne doit inclure product_id, variation_id ou sku.", "A linha deve incluir product_id, variation_id ou sku.", "La riga deve includere product_id, variation_id o sku." ],
	[ "Variable products require variation_id.", "Los productos variables requieren variation_id.", "Les produits variables nécessitent variation_id.", "Produtos variáveis exigem variation_id.", "I prodotti variabili richiedono variation_id." ],
	[ "Quantity must be greater than zero.", "La cantidad debe ser mayor que cero.", "La quantité doit être supérieure à zéro.", "A quantidade deve ser maior que zero.", "La quantità deve essere maggiore di zero." ],
	[ "The product does not exist.", "El producto no existe.", "Le produit n’existe pas.", "O produto não existe.", "Il prodotto non esiste." ],
	[ "The product is not published.", "El producto no está publicado.", "Le produit n’est pas publié.", "O produto não está publicado.", "Il prodotto non è pubblicato." ],
	[ "The product cannot be purchased.", "El producto no se puede comprar.", "Le produit ne peut pas être acheté.", "O produto não pode ser comprado.", "Il prodotto non può essere acquistato." ],
	[ "The product is out of stock.", "El producto está agotado.", "Le produit est en rupture de stock.", "O produto está fora de estoque.", "Il prodotto è esaurito." ],
	[ "The product does not have enough stock.", "El producto no tiene existencias suficientes.", "Le stock du produit est insuffisant.", "O produto não tem estoque suficiente.", "Il prodotto non dispone di scorte sufficienti." ],
	[ "Only simple products or specific variations are supported.", "Solo se admiten productos simples o variaciones específicas.", "Seuls les produits simples ou les variations spécifiques sont pris en charge.", "Apenas produtos simples ou variações específicas são compatíveis.", "Sono supportati solo prodotti semplici o variazioni specifiche." ],
	[ "WooCommerce cart tools", "Herramientas del carrito de WooCommerce", "Outils de panier WooCommerce", "Ferramentas do carrinho do WooCommerce", "Strumenti del carrello WooCommerce" ],
	[ "Configure CSV cart import and export without changing your WooCommerce cart templates.", "Configura la importación y exportación de carritos CSV sin modificar las plantillas del carrito de WooCommerce.", "Configurez l’importation et l’exportation de paniers CSV sans modifier les modèles de panier WooCommerce.", "Configure a importação e exportação de carrinhos CSV sem alterar os modelos de carrinho do WooCommerce.", "Configura l’importazione e l’esportazione dei carrelli CSV senza modificare i template del carrello WooCommerce." ],
	[ "Import or export cart", "Importar o exportar el carrito", "Importer ou exporter le panier", "Importar ou exportar o carrinho", "Importa o esporta il carrello" ],
	[ "Save your cart or load it from a CSV file.", "Guarda tu carrito o cárgalo desde un archivo CSV.", "Enregistrez votre panier ou chargez-le à partir d’un fichier CSV.", "Salve seu carrinho ou carregue-o de um arquivo CSV.", "Salva il carrello o caricalo da un file CSV." ],
	[ "Select CSV file", "Seleccionar archivo CSV", "Sélectionner un fichier CSV", "Selecionar arquivo CSV", "Seleziona file CSV" ],
	[ "Drop your CSV here", "Suelta aquí tu CSV", "Déposez votre CSV ici", "Solte seu CSV aqui", "Trascina qui il tuo CSV" ],
	[ "or", "o", "ou", "ou", "oppure" ],
	[ "click to select", "haz clic para seleccionar", "cliquez pour sélectionner", "clique para selecionar", "fai clic per selezionare" ],
	[ "CSV format, up to 2 MB and 500 product rows", "Formato CSV, hasta 2 MB y 500 filas de productos", "Format CSV, jusqu’à 2 Mo et 500 lignes de produits", "Formato CSV, até 2 MB e 500 linhas de produtos", "Formato CSV, fino a 2 MB e 500 righe di prodotti" ],
	[ "Ready to import", "Listo para importar", "Prêt à importer", "Pronto para importar", "Pronto per l’importazione" ],
	[ "Remove", "Eliminar", "Supprimer", "Remover", "Rimuovi" ],
	[ "Download CSV template", "Descargar plantilla CSV", "Télécharger le modèle CSV", "Baixar modelo CSV", "Scarica modello CSV" ],
	[ "Save changes", "Guardar cambios", "Enregistrer les modifications", "Salvar alterações", "Salva modifiche" ],
	[ "Saving…", "Guardando…", "Enregistrement…", "Salvando…", "Salvataggio…" ],
	[ "Settings sections", "Secciones de ajustes", "Sections des réglages", "Seções de configurações", "Sezioni delle impostazioni" ],
	[ "Loading...", "Cargando...", "Chargement...", "Carregando...", "Caricamento..." ],
	[ "Not connected. Verify your network connection.", "Sin conexión. Comprueba tu conexión de red.", "Non connecté. Vérifiez votre connexion réseau.", "Sem conexão. Verifique sua conexão de rede.", "Connessione assente. Verifica la connessione di rete." ],
	[ "Requested page not found [404].", "No se ha encontrado la página solicitada [404].", "Page demandée introuvable [404].", "Página solicitada não encontrada [404].", "Pagina richiesta non trovata [404]." ],
	[ "Internal server error [500].", "Error interno del servidor [500].", "Erreur interne du serveur [500].", "Erro interno do servidor [500].", "Errore interno del server [500]." ],
	[ "Requested JSON parse failed.", "No se ha podido analizar el JSON solicitado.", "L’analyse du JSON demandé a échoué.", "Falha ao analisar o JSON solicitado.", "Impossibile analizzare il JSON richiesto." ],
	[ "Timeout error.", "Error de tiempo de espera.", "Erreur de délai d’attente.", "Erro de tempo limite.", "Errore di timeout." ],
	[ "Ajax request aborted.", "Solicitud Ajax cancelada.", "Requête Ajax abandonnée.", "Solicitação Ajax cancelada.", "Richiesta Ajax annullata." ],
	[ "Uncaught error:", "Error no controlado:", "Erreur non interceptée :", "Erro não tratado:", "Errore non gestito:" ],
	[ "Review the highlighted issues", "Revisa los problemas resaltados", "Vérifiez les problèmes mis en évidence", "Revise os problemas destacados", "Controlla i problemi evidenziati" ],
	[ "OK", "Aceptar", "OK", "OK", "OK" ],
	[ "Oops...", "Vaya...", "Oups...", "Ops...", "Ops..." ],
	[ "Why do I have this issue?", "¿Por qué tengo este problema?", "Pourquoi ce problème se produit-il ?", "Por que estou com este problema?", "Perché si verifica questo problema?" ],
	[ "Generating CSV...", "Generando CSV...", "Génération du CSV...", "Gerando CSV...", "Generazione del CSV..." ],
	[ "Cart exported", "Carrito exportado", "Panier exporté", "Carrinho exportado", "Carrello esportato" ],
	[ "Select a valid .csv file.", "Selecciona un archivo .csv válido.", "Sélectionnez un fichier .csv valide.", "Selecione um arquivo .csv válido.", "Seleziona un file .csv valido." ],
	[ "Select a CSV file before continuing.", "Selecciona un archivo CSV antes de continuar.", "Sélectionnez un fichier CSV avant de continuer.", "Selecione um arquivo CSV antes de continuar.", "Seleziona un file CSV prima di continuare." ],
	[ "Reading CSV...", "Leyendo CSV...", "Lecture du CSV...", "Lendo CSV...", "Lettura del CSV..." ],
	[ "Cancel", "Cancelar", "Annuler", "Cancelar", "Annulla" ],
	[ "Importing products...", "Importando productos...", "Importation des produits...", "Importando produtos...", "Importazione dei prodotti..." ],
	[ "The request could not be processed.", "No se ha podido procesar la solicitud.", "La requête n’a pas pu être traitée.", "Não foi possível processar a solicitação.", "Non è stato possibile elaborare la richiesta." ],
	[ "Import preview", "Vista previa de la importación", "Aperçu de l’importation", "Prévia da importação", "Anteprima dell’importazione" ],
	[ "Review products before adding them to WooCommerce.", "Revisa los productos antes de añadirlos a WooCommerce.", "Vérifiez les produits avant de les ajouter à WooCommerce.", "Revise os produtos antes de adicioná-los ao WooCommerce.", "Controlla i prodotti prima di aggiungerli a WooCommerce." ],
	[ "Amount to import", "Cantidad a importar", "Quantité à importer", "Quantidade a importar", "Quantità da importare" ],
	[ "Product", "Producto", "Produit", "Produto", "Prodotto" ],
	[ "Product / variation", "Producto / variación", "Produit / variation", "Produto / variação", "Prodotto / variazione" ],
	[ "Qty.", "Cant.", "Qté", "Qtd.", "Qtà" ],
	[ "Price", "Precio", "Prix", "Preço", "Prezzo" ],
	[ "Subtotal", "Subtotal", "Sous-total", "Subtotal", "Subtotale" ],
	[ "Status", "Estado", "État", "Status", "Stato" ],
	[ "Ready", "Listo", "Prêt", "Pronto", "Pronto" ],
	[ "Row %d", "Fila %d", "Ligne %d", "Linha %d", "Riga %d" ],
	[ "With issue", "Con problema", "Avec un problème", "Com problema", "Con un problema" ],
	[ "SKU", "SKU", "UGS", "SKU", "SKU" ],
	[ "All valid products will be included in the import.", "Todos los productos válidos se incluirán en la importación.", "Tous les produits valides seront inclus dans l’importation.", "Todos os produtos válidos serão incluídos na importação.", "Tutti i prodotti validi saranno inclusi nell’importazione." ],
	[ "Importing products... %1$d / %2$d", "Importando productos... %1$d / %2$d", "Importation des produits... %1$d / %2$d", "Importando produtos... %1$d / %2$d", "Importazione dei prodotti... %1$d / %2$d" ],
	[ "Added: %d", "Añadidos: %d", "Ajoutés : %d", "Adicionados: %d", "Aggiunti: %d" ],
	[ "Cart imported", "Carrito importado", "Panier importé", "Carrinho importado", "Carrello importato" ],
	[ "Import completed with issues", "Importación completada con problemas", "Importation terminée avec des problèmes", "Importação concluída com problemas", "Importazione completata con problemi" ],
	[ "Close", "Cerrar", "Fermer", "Fechar", "Chiudi" ],
	[ "All products were added successfully.", "Todos los productos se han añadido correctamente.", "Tous les produits ont bien été ajoutés.", "Todos os produtos foram adicionados com sucesso.", "Tutti i prodotti sono stati aggiunti correttamente." ],
	[ "Import summary", "Resumen de la importación", "Résumé de l’importation", "Resumo da importação", "Riepilogo dell’importazione" ],
];

// Singular source, plural source, then singular/plural pairs for each locale.
const pluralRows = [
	[ "Import %d product", "Import %d products", "Importar %d producto", "Importar %d productos", "Importer %d produit", "Importer %d produits", "Importar %d produto", "Importar %d produtos", "Importa %d prodotto", "Importa %d prodotti" ],
	[ "valid product", "valid products", "producto válido", "productos válidos", "produit valide", "produits valides", "produto válido", "produtos válidos", "prodotto valido", "prodotti validi" ],
	[ "with issue", "with issues", "con problema", "con problemas", "avec un problème", "avec des problèmes", "com problema", "com problemas", "con un problema", "con problemi" ],
	[ "total product", "total products", "producto total", "productos totales", "produit au total", "produits au total", "produto no total", "produtos no total", "prodotto totale", "prodotti totali" ],
	[ "%d product with an issue will be skipped during import.", "%d products with issues will be skipped during import.", "Se omitirá %d producto con un problema durante la importación.", "Se omitirán %d productos con problemas durante la importación.", "%d produit présentant un problème sera ignoré pendant l’importation.", "%d produits présentant des problèmes seront ignorés pendant l’importation.", "%d produto com problema será ignorado durante a importação.", "%d produtos com problemas serão ignorados durante a importação.", "%d prodotto con un problema verrà ignorato durante l’importazione.", "%d prodotti con problemi verranno ignorati durante l’importazione." ],
	[ "With issue: %d", "With issues: %d", "Con problema: %d", "Con problemas: %d", "Avec un problème : %d", "Avec des problèmes : %d", "Com problema: %d", "Com problemas: %d", "Con un problema: %d", "Con problemi: %d" ],
	[ "Product added: %d", "Products added: %d", "Producto añadido: %d", "Productos añadidos: %d", "Produit ajouté : %d", "Produits ajoutés : %d", "Produto adicionado: %d", "Produtos adicionados: %d", "Prodotto aggiunto: %d", "Prodotti aggiunti: %d" ],
	[ "product added", "products added", "producto añadido", "productos añadidos", "produit ajouté", "produits ajoutés", "produto adicionado", "produtos adicionados", "prodotto aggiunto", "prodotti aggiunti" ],
	[ "issue", "issues", "problema", "problemas", "problème", "problèmes", "problema", "problemas", "problema", "problemi" ],
];

const localeCodes = Object.keys( locales );
const translations = Object.fromEntries( localeCodes.map( ( locale ) => [ locale, new Map() ] ) );

for ( const row of singularRows ) {
	localeCodes.forEach( ( locale ) => {
		translations[locale].set( row[0], [ row[locales[locale].translationIndex] ] );
	} );
}

for ( const row of pluralRows ) {
	localeCodes.forEach( ( locale ) => {
		const offset = 2 + ( locales[locale].translationIndex - 1 ) * 2;
		translations[locale].set( row[0], [ row[offset], row[offset + 1] ] );
	} );
}

const parseQuoted = ( value ) => JSON.parse( value );

const parsePot = ( source ) => {
	const entries = [];
	let entry = null;
	let activeField = null;

	const flush = () => {
		if ( entry?.msgid !== undefined ) {
			entries.push( entry );
		}
		entry = null;
		activeField = null;
	};

	for ( const line of source.split( /\r?\n/u ) ) {
		if ( line === '' ) {
			flush();
			continue;
		}

		entry ??= { comments: [] };

		if ( line.startsWith( '#' ) ) {
			entry.comments.push( line );
			continue;
		}

		const declaration = line.match( /^(msgid_plural|msgid)\s+(".*")$/u );
		if ( declaration ) {
			activeField = declaration[1] === 'msgid' ? 'msgid' : 'msgidPlural';
			entry[activeField] = parseQuoted( declaration[2] );
			continue;
		}

		if ( line.startsWith( 'msgstr' ) ) {
			activeField = null;
			continue;
		}

		if ( activeField && line.startsWith( '"' ) ) {
			entry[activeField] += parseQuoted( line );
		}
	}

	flush();
	return entries.filter( ( item ) => item.msgid !== '' );
};

const potEntries = parsePot( fs.readFileSync( potPath, 'utf8' ) );
const expectedIds = new Set( potEntries.map( ( entry ) => entry.msgid ) );

const placeholders = ( value ) => [ ...value.matchAll( /%(?:\d+\$)?[a-z]/giu ) ].map( ( match ) => match[0] ).sort();

for ( const locale of localeCodes ) {
	const catalog = translations[locale];
	const missing = [ ...expectedIds ].filter( ( id ) => ! catalog.has( id ) );
	const extra = [ ...catalog.keys() ].filter( ( id ) => ! expectedIds.has( id ) );

	if ( missing.length || extra.length ) {
		throw new Error( `${locale} catalog mismatch. Missing: ${missing.join( ' | ' ) || 'none'}. Extra: ${extra.join( ' | ' ) || 'none'}.` );
	}

	for ( const entry of potEntries ) {
		const translated = catalog.get( entry.msgid );
		const sourceForms = entry.msgidPlural ? [ entry.msgid, entry.msgidPlural ] : [ entry.msgid ];

		if ( translated.length !== sourceForms.length || translated.some( ( value ) => value.trim() === '' ) ) {
			throw new Error( `${locale} has incomplete forms for: ${entry.msgid}` );
		}

		sourceForms.forEach( ( source, index ) => {
			if ( JSON.stringify( placeholders( source ) ) !== JSON.stringify( placeholders( translated[index] ) ) ) {
				throw new Error( `${locale} does not preserve placeholders for: ${source}` );
			}
		} );
	}
}

const revisionDate = '2026-08-26 05:38+0000';

const headerFor = ( locale ) => [
	'Project-Id-Version: Cart Relay for WooCommerce 1.0.3',
	'Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/cart-relay',
	'POT-Creation-Date: 2026-08-25 23:12+0000',
	`PO-Revision-Date: ${revisionDate}`,
	'Last-Translator: Cart Relay contributors',
	`Language-Team: ${locales[locale].team}`,
	`Language: ${locale}`,
	'MIME-Version: 1.0',
	'Content-Type: text/plain; charset=UTF-8',
	'Content-Transfer-Encoding: 8bit',
	`Plural-Forms: ${locales[locale].pluralForms}`,
	'X-Generator: Cart Relay translation builder',
	'X-Domain: cart-relay',
	'',
].join( '\n' );

const quote = ( value ) => JSON.stringify( value );

const buildPo = ( locale ) => {
	const lines = [
		'# Copyright (C) 2026 Gilbert Rodríguez',
		'# This file is distributed under the GPL-2.0-or-later.',
		`# ${locales[locale].languageName} translation for Cart Relay for WooCommerce.`,
		'msgid ""',
		'msgstr ""',
		...headerFor( locale ).split( '\n' ).map( ( line ) => quote( `${line}\n` ) ),
		'',
	];

	for ( const entry of potEntries ) {
		const translated = translations[locale].get( entry.msgid );
		lines.push( ...entry.comments, `msgid ${quote( entry.msgid )}` );
		if ( entry.msgidPlural ) {
			lines.push( `msgid_plural ${quote( entry.msgidPlural )}`, `msgstr[0] ${quote( translated[0] )}`, `msgstr[1] ${quote( translated[1] )}` );
		} else {
			lines.push( `msgstr ${quote( translated[0] )}` );
		}
		lines.push( '' );
	}

	return lines.join( '\n' );
};

const compareBuffers = ( left, right ) => left.length === right.length && left.equals( right );

const buildMo = ( locale ) => {
	const messages = [ { original: '', translated: headerFor( locale ) } ];
	for ( const entry of potEntries ) {
		const translated = translations[locale].get( entry.msgid );
		messages.push( {
			original: entry.msgidPlural ? `${entry.msgid}\0${entry.msgidPlural}` : entry.msgid,
			translated: translated.join( '\0' ),
		} );
	}

	messages.sort( ( left, right ) => Buffer.from( left.original ).compare( Buffer.from( right.original ) ) );
	const originals = messages.map( ( message ) => Buffer.from( `${message.original}\0`, 'utf8' ) );
	const translated = messages.map( ( message ) => Buffer.from( `${message.translated}\0`, 'utf8' ) );
	const count = messages.length;
	const originalTableOffset = 28;
	const translatedTableOffset = originalTableOffset + count * 8;
	const stringDataOffset = translatedTableOffset + count * 8;
	const totalLength = stringDataOffset + originals.reduce( ( total, value ) => total + value.length, 0 ) + translated.reduce( ( total, value ) => total + value.length, 0 );
	const output = Buffer.alloc( totalLength );

	output.writeUInt32LE( 0x950412de, 0 );
	output.writeUInt32LE( 0, 4 );
	output.writeUInt32LE( count, 8 );
	output.writeUInt32LE( originalTableOffset, 12 );
	output.writeUInt32LE( translatedTableOffset, 16 );
	output.writeUInt32LE( 0, 20 );
	output.writeUInt32LE( stringDataOffset, 24 );

	let cursor = stringDataOffset;
	originals.forEach( ( value, index ) => {
		output.writeUInt32LE( value.length - 1, originalTableOffset + index * 8 );
		output.writeUInt32LE( cursor, originalTableOffset + index * 8 + 4 );
		value.copy( output, cursor );
		cursor += value.length;
	} );

	translated.forEach( ( value, index ) => {
		output.writeUInt32LE( value.length - 1, translatedTableOffset + index * 8 );
		output.writeUInt32LE( cursor, translatedTableOffset + index * 8 + 4 );
		value.copy( output, cursor );
		cursor += value.length;
	} );

	return output;
};

const buildJson = ( locale ) => {
	const messages = {
		'': {
			domain: 'messages',
			'plural-forms': locales[locale].pluralForms,
			lang: locale,
		},
	};
	const references = new Set();

	for ( const entry of potEntries ) {
		if ( ! entry.comments.some( ( comment ) => /dist\/assets\/[^\s:]+\.js/u.test( comment ) ) ) {
			continue;
		}

		entry.comments.forEach( ( comment ) => {
			for ( const match of comment.matchAll( /dist\/assets\/[^\s:]+\.js/gu ) ) {
				references.add( match[0] );
			}
		} );
		messages[entry.msgid] = translations[locale].get( entry.msgid );
	}

	return `${JSON.stringify( {
		'translation-revision-date': revisionDate,
		generator: 'Cart Relay translation builder',
		domain: 'messages',
		locale_data: { messages },
		comment: { reference: [ ...references ].sort().join( ', ' ) },
	} )}\n`;
};

const expectedFiles = new Map();
for ( const locale of localeCodes ) {
	expectedFiles.set( `cart-relay-${locale}.po`, Buffer.from( buildPo( locale ), 'utf8' ) );
	expectedFiles.set( `cart-relay-${locale}.mo`, buildMo( locale ) );
	expectedFiles.set( `cart-relay-${locale}-${scriptHandle}.json`, Buffer.from( buildJson( locale ), 'utf8' ) );
}

for ( const [ filename, expected ] of expectedFiles ) {
	const outputPath = path.join( languagesDirectory, filename );
	if ( checkOnly ) {
		if ( ! fs.existsSync( outputPath ) || ! compareBuffers( fs.readFileSync( outputPath ), expected ) ) {
			throw new Error( `${filename} is missing or stale. Run npm run build:i18n.` );
		}
	} else {
		fs.writeFileSync( outputPath, expected );
	}
}

const mode = checkOnly ? 'Verified' : 'Built';
process.stdout.write( `${mode} ${localeCodes.length} complete translation catalogs (${potEntries.length} messages each) with PO, MO, and JavaScript JSON files.\n` );
