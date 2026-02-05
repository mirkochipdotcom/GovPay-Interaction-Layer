// Load wallets configuration from JSON
async function loadWalletsConfig() {
  try {
    const response = await fetch('/static/config/wallets-config.json');
    if (!response.ok) {
      console.debug('Wallets config file not found, all wallets enabled');
      return null;
    }
    const config = await response.json();
    window.WALLETS_CONFIG = config;
    console.debug('Wallets config loaded:', config);
    return config;
  } catch (err) {
    console.debug('Error loading wallets config:', err);
    return null;
  }
}

// Load configuration override for disco page personalization
async function loadConfigOverride() {
  try {
    const response = await fetch('/static/config/config-override.json');
    if (!response.ok) {
      console.debug('Config override file not found, using defaults');
      return null;
    }
    const config = await response.json();
    return config;
  } catch (err) {
    console.debug('Error loading config override:', err);
    return null;
  }
}

// Apply organization name and logo to header
function applyOrganizationBranding(config) {
  if (!config || !config.organization) {
    return;
  }

  const orgBrand = document.querySelector('.navbar-brand');
  if (orgBrand && config.organization.name) {
    orgBrand.textContent = '';
    const strong = document.createElement('strong');
    strong.textContent = config.organization.name;
    orgBrand.appendChild(strong);
    
    // Make logo clickable to organization website
    if (config.organization.url) {
      const link = document.createElement('a');
      link.href = config.organization.url;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.style.textDecoration = 'none';
      link.style.color = 'white';
      
      // Move the strong element into the link
      link.appendChild(strong);
      orgBrand.textContent = '';
      orgBrand.appendChild(link);
    }
  }
}

// Apply footer links personalization
function applyFooterPersonalization(config) {
  if (!config || !config.footer) {
    return;
  }

  const footerLinks = {
    'footer-legal': config.footer.legal,
    'footer-privacy': config.footer.privacy,
    'footer-accessibility': config.footer.accessibility
  };

  Object.entries(footerLinks).forEach(([id, link]) => {
    const element = document.getElementById(id);
    if (element && link) {
      element.textContent = link.text || '';
      element.href = link.url || '';
    }
  });
}

// Apply header branding with logo if available
function applyHeaderBranding(config) {
  if (!config || !config.organization || !config.organization.logo) {
    return;
  }

  const header = document.querySelector('.it-header-wrapper');
  if (!header) {
    return;
  }

  // Try to find or create a logo container in the header
  let logoContainer = header.querySelector('.org-logo-container');
  if (!logoContainer) {
    // Create logo container if it doesn't exist
    logoContainer = document.createElement('div');
    logoContainer.className = 'org-logo-container';
    logoContainer.style.marginRight = '15px';
    
    // Insert after the first container
    const slimWrapper = header.querySelector('.it-header-slim-wrapper-content');
    if (slimWrapper) {
      const navBrand = slimWrapper.querySelector('.navbar-brand');
      if (navBrand && navBrand.parentElement) {
        navBrand.parentElement.insertBefore(logoContainer, navBrand);
      }
    }
  }

  // Add or update logo
  let img = logoContainer.querySelector('img');
  if (!img) {
    img = document.createElement('img');
    logoContainer.appendChild(img);
  }
  
  img.src = config.organization.logo;
  img.alt = config.organization.name || 'Organization Logo';
  img.style.maxHeight = '40px';
  img.style.objectFit = 'contain';
  
  // Make logo clickable to organization website
  if (config.organization.url && !img.parentElement.tagName.toLowerCase() === 'a') {
    const link = document.createElement('a');
    link.href = config.organization.url;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    logoContainer.insertBefore(link, img);
    link.appendChild(img);
  }
}

// Main initialization
document.addEventListener('DOMContentLoaded', async function() {
  // Load wallets configuration before wallets.js runs
  await loadWalletsConfig();
  
  const config = await loadConfigOverride();
  if (config) {
    applyOrganizationBranding(config);
    applyFooterPersonalization(config);
    applyHeaderBranding(config);
  }
});
