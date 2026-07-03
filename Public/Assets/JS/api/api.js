/**
 * AttendQR — Capa de comunicación con la API REST
 * Todas las peticiones al backend pasan por aquí.
 * Respuesta estándar: { success, message, data }
 */
const Api = (() => {

  // Detectar base path dinámicamente desde la URL actual
  const BASE = (() => {
    const parts = window.location.pathname.split('/');
    // Busca el segmento 'AttendQR' o usa la raíz
    const idx = parts.findIndex(p => p.toLowerCase() === 'attendqr');
    const prefix = idx >= 0 ? '/' + parts.slice(1, idx + 1).join('/') : '';
    return prefix + '/Public/api';
  })();

  async function request(endpoint, options = {}) {
    const url = `${BASE}${endpoint}`;
    const res = await fetch(url, {
      credentials: 'same-origin',
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(options.headers ?? {}),
      },
    });

    let json;
    try { json = await res.json(); } catch { json = {}; }

    // Sesión expirada: redirigir a login automáticamente.
    // Excluir /auth/* para no entrar en bucle cuando el login mismo falla.
    if (res.status === 401 && !endpoint.startsWith('/auth/')) {
      sessionStorage.removeItem('attendqr_usuario');
      const parts  = window.location.pathname.split('/');
      const idx    = parts.findIndex(p => p.toLowerCase() === 'attendqr');
      const prefix = idx >= 0 ? '/' + parts.slice(1, idx + 1).join('/') : '';
      window.location.href = prefix + '/Public/Views/login.php';
      return; // detener ejecución
    }

    if (!res.ok || json.success === false) {
      const err = new Error(json.message ?? `Error ${res.status}`);
      err.status = res.status;
      err.data   = json.data ?? null;
      throw err;
    }

    return json.data ?? json;
  }

  const get  = (ep, params) => {
    const qs = params && Object.keys(params).length ? '?' + new URLSearchParams(params).toString() : '';
    return request(`${ep}${qs}`);
  };
  const post = (ep, body)   => request(ep, { method: 'POST',   body: JSON.stringify(body ?? {}) });
  const put  = (ep, body)   => request(ep, { method: 'PUT',    body: JSON.stringify(body ?? {}) });

  const auth = {
    login:     (body)  => post('/auth/login', body),
    logout:    ()      => post('/auth/logout'),
    verificar: ()      => get('/auth/verificar'),
  };

  const docentes = {
    consultar:  (id)       => get(`/docentes/consultar/${id}`),
    actualizar: (id, body) => put(`/docentes/actualizar/${id}`, body),
  };

  const aprendices = {
    consultar:  (id)       => get(`/aprendices/consultar/${id}`),
    actualizar: (id, body) => put(`/aprendices/actualizar/${id}`, body),
  };

  const fichas = {
    listar:    (params) => get('/fichas/listar', params),
    historial: (id)     => get(`/fichas/historial/${id}`),
  };

  const jornadas = {
    listar: () => get('/jornadas/listar'),
  };

  const trimestres = {
    listar: (params) => get('/trimestres/listar', params),
  };

  const sesiones = {
    crear:        (body)    => post('/sesiones/crear', body),
    listar:       (params)  => get('/sesiones/listar', params),
    detalle:      (id)      => get(`/sesiones/detalle/${id}`),
    activa:       (idFicha) => get(`/sesiones/activa/${idFicha}`),
    cerrar:       (id)      => post(`/sesiones/cerrar/${id}`),
    asistencias:  (id)      => get(`/sesiones/asistencias/${id}`),
    estadisticas: (id)      => get(`/sesiones/estadisticas/${id}`),
  };

  const qr = {
    generar:     (idSesion) => post(`/qr/generar/${idSesion}`),
    tokenActivo: (idSesion) => get(`/qr/token-activo/${idSesion}`),
    validar:     (token)    => post('/qr/validar', { token }),
  };

  const asistencias = {
    historial:  (idAprendiz) => get(`/asistencias/historial/${idAprendiz}`),
    registrar:  (body)       => post('/asistencias/registrar', body),
  };

  const estadisticas = {
    dashboard:  (params) => get('/estadisticas/dashboard', params),
    resumen:    ()       => get('/estadisticas/resumen'),
    asistencia: (params) => get('/estadisticas/asistencia', params),
  };

  return { auth, docentes, aprendices, fichas, jornadas, trimestres,
           sesiones, qr, asistencias, estadisticas };
})();
