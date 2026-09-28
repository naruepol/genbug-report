export type ProjectStatus = 'draft' | 'published' | 'closed';
export type BugStatus = 'open' | 'in_progress' | 'fixed' | 'closed' | 'rejected' | 'duplicate';
export type VerificationStatus = 'pending' | 'verified' | 'rejected' | 'duplicate';
export type BugSeverity = 'critical' | 'high' | 'medium' | 'low';
export type BugPriority = 'high' | 'medium' | 'low';
export type BugCategory = 'functional' | 'ui_ux' | 'performance' | 'compatibility' | 'content' | 'other' | 'not_sure';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    avatar_url: string | null;
    is_admin: boolean;
}

export interface SharedProps {
    appName: string;
    auth: { user: AuthUser | null };
    [key: string]: unknown;
}

export interface FlashData {
    success?: string;
    error?: string;
}

export interface Project {
    id: number;
    project_code: string;
    name: string;
    description: string;
    image_url: string | null;
    demo_url: string | null;
    repository_url: string | null;
    technology_stack: string | null;
    technologies: string[];
    presentation_date: string | null;
    status: ProjectStatus;
    /** The admin's on/off switch for public bug reports. */
    bug_reporting_enabled: boolean;
    /** True when the project is Published and bug reporting is switched on. */
    accepts_bug_reports: boolean;
    public_url: string;
    bugs_count?: number;
    pending_bugs_count?: number;
    verified_bugs_count?: number;
    fixed_bugs_count?: number;
    created_at: string | null;
    updated_at: string | null;
}

export interface Screenshot {
    id: number;
    url: string;
    mime_type: string;
    file_name?: string;
    file_size?: number;
}

/** Bug fields visible to everyone. Reporter identity and admin notes never appear here. */
export interface PublicBug {
    bug_code: string;
    title: string;
    description: string;
    category: BugCategory | null;
    severity: BugSeverity | null;
    priority: BugPriority | null;
    score: number | null;
    status: BugStatus;
    verification_status: VerificationStatus;
    steps_to_reproduce: string | null;
    expected_result: string | null;
    actual_result: string | null;
    screenshots?: Screenshot[];
    project?: { id?: number; project_code: string; name: string; status: ProjectStatus; accepts_bug_reports?: boolean };
    created_at: string | null;
    updated_at: string | null;
}

export interface AdminBug extends PublicBug {
    id: number;
    page_screen: string | null;
    browser: string | null;
    operating_system: string | null;
    device: string | null;
    reporter_name: string | null;
    reporter_email: string | null;
    reporter_ip: string | null;
    admin_note: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}

export interface PublicBugFilters {
    search: string;
    status: BugStatus | null;
    severity: BugSeverity | null;
    verification: VerificationStatus | null;
}

export interface AdminBugFilters extends PublicBugFilters {
    project: number | null;
    priority: BugPriority | null;
    from: string | null;
    to: string | null;
}

export interface BugSummary {
    total: number;
    verification: Record<VerificationStatus, number>;
    status: Record<BugStatus, number>;
}

export interface BreakdownRow {
    value: string | null;
    label: string;
    count: number;
}

export interface AuditEntry {
    id: number;
    action: string;
    entity_type?: string;
    entity_id?: number | null;
    metadata: Record<string, unknown> | null;
    admin: string | null;
    created_at: string | null;
}
