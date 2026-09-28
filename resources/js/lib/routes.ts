/** URL helpers mirroring routes/web.php. */
export const routes = {
    home: () => '/',
    projects: () => '/projects',
    project: (code: string) => `/project/${encodeURIComponent(code)}`,
    reportBug: (code: string) => `/project/${encodeURIComponent(code)}/report-bug`,
    bug: (code: string) => `/bug/${encodeURIComponent(code)}`,

    login: () => '/login',
    googleLogin: () => '/auth/google',
    logout: () => '/logout',

    admin: {
        dashboard: () => '/admin',
        projects: () => '/admin/projects',
        createProject: () => '/admin/projects/create',
        project: (id: number) => `/admin/projects/${id}`,
        editProject: (id: number) => `/admin/projects/${id}/edit`,
        publishProject: (id: number) => `/admin/projects/${id}/publish`,
        closeProject: (id: number) => `/admin/projects/${id}/close`,
        bugReporting: (id: number) => `/admin/projects/${id}/bug-reporting`,
        generateQrCode: (id: number) => `/admin/projects/${id}/qr-code`,
        downloadQrCode: (id: number, format: 'png' | 'svg') => `/admin/projects/${id}/qr-code.${format}`,
        bugs: (params: Record<string, string | number> = {}) => {
            const query = new URLSearchParams(Object.entries(params).map(([key, value]) => [key, String(value)]));
            const qs = query.toString();

            return qs ? `/admin/bugs?${qs}` : '/admin/bugs';
        },
        bug: (id: number) => `/admin/bugs/${id}`,
    },
};
