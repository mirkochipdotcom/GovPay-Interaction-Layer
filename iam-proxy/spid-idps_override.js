// * spid-idps.js *
// This script populate the SPID button with the SPID IDPS
//
// ** Configuration ***
// const idps define list of SPID IDPs
// - entityName - string with IDP name
// - entityID - string with IDP entityID
// - logo - url of IDP logo image
const idps = [
  {"entityName": "SPID Test", "entityID": "https://demo.spid.gov.it", "logo": ""},
  {"entityName": "Aruba ID", "entityID": "https://loginspid.aruba.it", "logo": "/static/spid/spid-idp-arubaid.svg"},
  {"entityName": "Infocert ID", "entityID": "https://identity.infocert.it", "logo": "/static/spid/spid-idp-infocertid.svg"},
  {"entityName": "Intesa ID", "entityID": "https://spid.intesa.it", "logo": "/static/spid/spid-idp-intesaid.svg"},
  {"entityName": "Lepida ID", "entityID": "https://id.lepida.it/idp/shibboleth", "logo": "/static/spid/spid-idp-lepidaid.svg"},
  {"entityName": "Namirial ID", "entityID": "https://idp.namirialtsp.com/idp", "logo": "/static/spid/spid-idp-namirialid.svg"},
  {"entityName": "Poste ID", "entityID": "https://posteid.poste.it", "logo": "/static/spid/spid-idp-posteid.svg"},
  {"entityName": "Sielte ID", "entityID": "https://identity.sieltecloud.it", "logo": "/static/spid/spid-idp-sielteid.svg"},
  {"entityName": "SPIDItalia Register.it", "entityID": "https://spid.register.it", "logo": "/static/spid/spid-idp-spiditalia.svg"},
  {"entityName": "Tim ID", "entityID": "https://login.id.tim.it/affwebservices/public/saml2sso", "logo": "/static/spid/spid-idp-timid.svg"},
  {"entityName": "TeamSystem ID", "entityID": "https://spid.teamsystem.com/idp", "logo": "/static/spid/spid-idp-teamsystemid.svg"}
].sort(() => Math.random() - 0.5)

// ** Values **
const urlParams = new URLSearchParams(window.location.search);
const servicePath = urlParams.get("return");
const entityID = urlParams.get('entityID');

// function addIdpEntry creates a Bootstrap wallet-box div with the IdP link
//
// options:
// - data - is an object with "entityName", "entityID" and "logo" values
// - container - is the container element where the wallet-box will be added
function addIdpEntry(data, container) {
  let col = document.createElement('div');
  col.className = 'col-12 col-md-6 col-lg-4 mb-3';
  
  let walletBox = document.createElement('div');
  walletBox.className = 'wallet-box border rounded p-3 h-100 d-flex align-items-center';
  
  let link = document.createElement('a');
  link.href = `${servicePath}?entityID=${data['entityID']}&return=${servicePath}`;
  link.className = 'text-decoration-none text-dark d-flex align-items-center w-100';
  
  if (data['logo']) {
    let img = document.createElement('img');
    img.src = data['logo'];
    img.alt = data['entityName'];
    img.style.maxWidth = '60px';
    img.style.marginRight = '15px';
    link.appendChild(img);
  }
  
  let span = document.createElement('span');
  span.textContent = data['entityName'];
  span.style.fontSize = '1.1rem';
  link.appendChild(span);
  
  walletBox.appendChild(link);
  col.appendChild(walletBox);
  container.appendChild(col);
}

// Execute immediately when container is available
(function waitForContainer() {
  var container = document.querySelector('div#wallets-container');
  
  if (container) {
    if (!entityID) { 
      alert('To use a Discovery Service you must come from a Service Provider');
      return;
    }
    
    for (var i = 0; i < idps.length; i++) { 
      addIdpEntry(idps[i], container); 
    }
  } else {
    setTimeout(waitForContainer, 50);
  }
})();
