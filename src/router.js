const routes = {};
let currentCleanup = null;

export function registerRoute(path, handler) {
  routes[path] = handler;
}

export function navigate(path) {
  window.location.hash = path;
}

export function getCurrentRoute() {
  return window.location.hash.slice(1) || '/';
}

export function getRouteParams() {
  const hash = window.location.hash.slice(1);
  const [, query] = hash.split('?');
  if (!query) return {};
  const params = {};
  new URLSearchParams(query).forEach((value, key) => {
    params[key] = value;
  });
  return params;
}

export function startRouter() {
  const handleRoute = async () => {
    if (currentCleanup) {
      currentCleanup();
      currentCleanup = null;
    }

    const hash = window.location.hash.slice(1) || '/';
    const path = hash.split('?')[0];
    const handler = routes[path] || routes['/'];
    if (handler) {
      currentCleanup = await handler();
    }
  };

  window.addEventListener('hashchange', handleRoute);
  handleRoute();
}
