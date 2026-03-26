

(function() {
    // Récupérer le script actuel
    const currentScript = document.currentScript;
    
    if (!currentScript) {
        console.error('SondagePro: Script non trouvé');
        return;
    }
    
    // Récupérer l'ID du sondage depuis l'attribut data
    const sondageId = currentScript.getAttribute('data-sondage');
    
    if (!sondageId) {
        console.error('SondagePro: ID du sondage manquant');
        return;
    }
    
    // Récupérer l'URL de base du script
    const scriptSrc = currentScript.src;
    const baseUrl = scriptSrc.substring(0, scriptSrc.lastIndexOf('/js/'));
    
    // Récupérer les options de personnalisation
    const theme = currentScript.getAttribute('data-theme') || 'light';
    const hideResults = currentScript.hasAttribute('data-hide-results');
    const compact = currentScript.hasAttribute('data-compact');
    const autoRefresh = currentScript.hasAttribute('data-auto-refresh');
    
    // Construire l'URL du widget
    let widgetUrl = `${baseUrl}/embed.php?id=${sondageId}`;
    if (theme === 'dark') widgetUrl += '&theme=dark';
    if (hideResults) widgetUrl += '&hide_results=1';
    if (compact) widgetUrl += '&compact=1';
    if (autoRefresh) widgetUrl += '&auto_refresh=1';
    
    // Trouver le conteneur du widget
    const container = document.getElementById(`sondage-widget-${sondageId}`);
    
    if (!container) {
        console.error(`SondagePro: Conteneur #sondage-widget-${sondageId} non trouvé`);
        return;
    }
    
    // Créer l'iframe
    const iframe = document.createElement('iframe');
    iframe.src = widgetUrl;
    iframe.width = '100%';
    iframe.height = compact ? '350' : '450';
    iframe.frameBorder = '0';
    iframe.style.borderRadius = '12px';
    iframe.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
    iframe.style.maxWidth = '100%';
    iframe.setAttribute('allowtransparency', 'true');
    
    // Ajouter l'iframe au conteneur
    container.appendChild(iframe);
    
    // Écouter les messages de l'iframe pour ajuster la hauteur
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'sondagepro-resize') {
            if (event.data.sondageId === sondageId) {
                iframe.height = event.data.height;
            }
        }
    });
})();
