/**
 * ImageLab SaaS Core Front-End Engine
 * Handles user authentication states, project workspace saving/loading,
 * developer key operations, billing/PayPal simulation, and Admin analytics charts.
 */
const ImageLabSaaS = {
    state: {
        authenticated: false,
        user: null,
        csrfToken: '',
        projects: [],
        keys: [],
        invoices: [],
        users: []
    },

    init() {
        console.log('ImageLab SaaS Engine Init.');
        this.fetchSession();
        this.bindEvents();
    },

    fetchSession() {
        fetch(`${ImageLabBaseUrl}/api/auth/session.php`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    this.state.csrfToken = res.csrf_token;
                    this.state.authenticated = res.authenticated;
                    this.state.user = res.user;

                    // Expose CSRF globally to other scripts if needed
                    window.ImageLabCsrfToken = res.csrf_token;

                    this.syncAuthUI();

                    if (this.state.authenticated) {
                        this.loadUserDashboard();
                        this.loadProjects();
                        this.loadDeveloperKeys();
                        this.loadBilling();
                        if (this.isAdmin()) {
                            this.loadAdminPanel();
                        }
                    }
                }
            })
            .catch(err => console.error('Session check failed:', err));
    },

    bindEvents() {
        // Register form submit
        const regForm = document.getElementById('saas-register-form');
        if (regForm) {
            regForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleRegister();
            });
        }

        // Login form submit
        const loginForm = document.getElementById('saas-login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }

        // Forgot password request
        const forgotForm = document.getElementById('saas-forgot-form');
        if (forgotForm) {
            forgotForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleForgotPassword();
            });
        }

        // Reset password action
        const resetForm = document.getElementById('saas-reset-form');
        if (resetForm) {
            resetForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleResetPassword();
            });
        }
    },

    syncAuthUI() {
        const loggedInViews = document.querySelectorAll('.saas-logged-in-only');
        const loggedOutViews = document.querySelectorAll('.saas-logged-out-only');
        const adminViews = document.querySelectorAll('.saas-admin-only');

        // Nav bars profile name
        const profileBtnName = document.querySelector('#profileDropdown span');
        const profileBtnAvatar = document.querySelector('#profileDropdown .avatar');

        if (this.state.authenticated) {
            loggedInViews.forEach(el => el.classList.remove('d-none'));
            loggedOutViews.forEach(el => el.classList.add('d-none'));

            if (profileBtnName) profileBtnName.textContent = this.state.user.name;
            if (profileBtnAvatar) {
                profileBtnAvatar.textContent = this.state.user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            }

            // Show Admin Panel nav item if administrator
            if (this.isAdmin()) {
                adminViews.forEach(el => el.classList.remove('d-none'));
            } else {
                adminViews.forEach(el => el.classList.add('d-none'));
            }
        } else {
            loggedInViews.forEach(el => el.classList.add('d-none'));
            loggedOutViews.forEach(el => el.classList.remove('d-none'));
            adminViews.forEach(el => el.classList.add('d-none'));

            if (profileBtnName) profileBtnName.textContent = 'Guest';
            if (profileBtnAvatar) profileBtnAvatar.textContent = 'G';
        }
    },

    isAdmin() {
        return this.state.user && (this.state.user.role === 'admin' || this.state.user.role === 'super_admin');
    },

    handleRegister() {
        const name = document.getElementById('reg-name').value;
        const email = document.getElementById('reg-email').value;
        const password = document.getElementById('reg-password').value;
        const alertBox = document.getElementById('reg-alert-box');

        alertBox.innerHTML = '';

        const params = new URLSearchParams();
        params.append('name', name);
        params.append('email', email);
        params.append('password', password);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/auth/register.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alertBox.innerHTML = `<div class="alert alert-success py-2" style="font-size:13px;">${res.message}</div>`;
                document.getElementById('saas-register-form').reset();
                setTimeout(() => {
                    this.showAuthModal('login');
                }, 2000);
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger py-2" style="font-size:13px;">${res.message}</div>`;
            }
        })
        .catch(() => {
            alertBox.innerHTML = '<div class="alert alert-danger py-2">Network error. Try again.</div>';
        });
    },

    handleLogin() {
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;
        const remember = document.getElementById('login-remember').checked;
        const alertBox = document.getElementById('login-alert-box');

        alertBox.innerHTML = '';

        const params = new URLSearchParams();
        params.append('email', email);
        params.append('password', password);
        params.append('remember', remember);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/auth/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alertBox.innerHTML = '<div class="alert alert-success py-2" style="font-size:13px;">Login successful! Loading dashboard...</div>';
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger py-2" style="font-size:13px;">${res.message}</div>`;
            }
        })
        .catch(() => {
            alertBox.innerHTML = '<div class="alert alert-danger py-2">Network error. Try again.</div>';
        });
    },

    handleForgotPassword() {
        const email = document.getElementById('forgot-email').value;
        const alertBox = document.getElementById('forgot-alert-box');
        alertBox.innerHTML = '';

        const params = new URLSearchParams();
        params.append('email', email);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/auth/forgot_password.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alertBox.innerHTML = `<div class="alert alert-success py-2" style="font-size:13px;">${res.message}</div>`;
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger py-2" style="font-size:13px;">${res.message}</div>`;
            }
        });
    },

    handleResetPassword() {
        const password = document.getElementById('reset-password-val').value;
        const confirm = document.getElementById('reset-password-confirm').value;
        const alertBox = document.getElementById('reset-alert-box');
        alertBox.innerHTML = '';

        if (password !== confirm) {
            alertBox.innerHTML = '<div class="alert alert-danger py-2">Passwords do not match.</div>';
            return;
        }

        // Extract token from URL hash (e.g. #panel-reset-password&token=123)
        const hash = location.hash;
        const tokenMatch = hash.match(/token=([a-f0-9]+)/i);
        const token = tokenMatch ? tokenMatch[1] : '';

        if (!token) {
            alertBox.innerHTML = '<div class="alert alert-danger py-2">Invalid or missing reset token.</div>';
            return;
        }

        const params = new URLSearchParams();
        params.append('token', token);
        params.append('password', password);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/auth/reset_password.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alertBox.innerHTML = `<div class="alert alert-success py-2" style="font-size:13px;">${res.message}</div>`;
                setTimeout(() => {
                    location.hash = '';
                    this.showAuthModal('login');
                }, 2000);
            } else {
                alertBox.innerHTML = `<div class="alert alert-danger py-2" style="font-size:13px;">${res.message}</div>`;
            }
        });
    },

    logout() {
        const params = new URLSearchParams();
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/auth/logout.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(() => {
            location.reload();
        });
    },

    showAuthModal(mode = 'login') {
        const modalEl = document.getElementById('saas-auth-modal');
        if (!modalEl) return;

        // Toggle card forms
        document.getElementById('saas-login-card').classList.add('d-none');
        document.getElementById('saas-register-card').classList.add('d-none');
        document.getElementById('saas-forgot-card').classList.add('d-none');

        if (mode === 'login') {
            document.getElementById('saas-login-card').classList.remove('d-none');
        } else if (mode === 'register') {
            document.getElementById('saas-register-card').classList.remove('d-none');
        } else if (mode === 'forgot') {
            document.getElementById('saas-forgot-card').classList.remove('d-none');
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    },

    loadUserDashboard() {
        // We'll update the limits widgets by querying plan status
        fetch(`${ImageLabBaseUrl}/api/auth/session.php`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.user) {
                    document.getElementById('ud-user-name').textContent = res.user.name;
                    document.getElementById('ud-user-role').textContent = res.user.role.toUpperCase();
                }
            });

        // Load recent activity or usage alerts
        fetch(`${ImageLabBaseUrl}/api/saas/projects.php?action=list`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const tbody = document.getElementById('ud-recent-projects-tbody');
                    if (tbody) {
                        tbody.innerHTML = '';
                        if (res.data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No saved projects found.</td></tr>';
                        }
                        res.data.slice(0, 5).forEach(proj => {
                            tbody.innerHTML += `
                                <tr>
                                    <td><span class="fw-medium">${proj.project_name}</span></td>
                                    <td>${proj.updated_at}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary py-1" onclick="ImageLabSaaS.loadProjectWorkspace(${proj.id})">Open</button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                }
            });
    },

    loadProjects() {
        fetch(`${ImageLabBaseUrl}/api/saas/projects.php?action=list`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    this.state.projects = res.data;
                    const listEl = document.getElementById('workspace-project-list');
                    if (listEl) {
                        listEl.innerHTML = '';
                        if (res.data.length === 0) {
                            listEl.innerHTML = '<div class="text-muted p-2 text-center" style="font-size:12px;">No saved projects</div>';
                        }
                        res.data.forEach(p => {
                            listEl.innerHTML += `
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom hover-bg" style="font-size:13px;">
                                    <span class="text-truncate fw-medium" style="max-width:140px;" title="${p.project_name}">${p.project_name}</span>
                                    <div>
                                        <button class="btn btn-link text-primary p-0 me-2" style="font-size:12px;" onclick="ImageLabSaaS.loadProjectWorkspace(${p.id})">Load</button>
                                        <button class="btn btn-link text-danger p-0" style="font-size:12px;" onclick="ImageLabSaaS.deleteProject(${p.id})">Delete</button>
                                    </div>
                                </div>
                            `;
                        });
                    }
                }
            });
    },

    saveCurrentWorkspace() {
        if (!this.state.authenticated) {
            this.showAuthModal('login');
            return;
        }

        const name = prompt("Enter a name for this workspace project:", "My Design Project");
        if (!name) return;

        // Collect all global states from main ImageLab controller
        const data = {
            activeFilename: ImageLab.state.activeFilename,
            activeOriginalName: ImageLab.state.activeOriginalName,
            activeProcessedFilename: ImageLab.state.activeProcessedFilename,
            activeWidth: ImageLab.state.activeWidth,
            activeHeight: ImageLab.state.activeHeight,
            activeExtension: ImageLab.state.activeExtension,
            activeSize: ImageLab.state.activeSize
        };

        const params = new URLSearchParams();
        params.append('action', 'save');
        params.append('project_name', name);
        params.append('project_data', JSON.stringify(data));
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/saas/projects.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert('Workspace saved successfully!');
                this.loadProjects();
            } else {
                alert('Failed to save workspace: ' + res.message);
            }
        });
    },

    loadProjectWorkspace(projectId) {
        fetch(`${ImageLabBaseUrl}/api/saas/projects.php?action=load&id=${projectId}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const data = res.data.project_data;

                    // Sync into ImageLab active editor states
                    ImageLab.state.activeFilename = data.activeFilename;
                    ImageLab.state.activeOriginalName = data.activeOriginalName;
                    ImageLab.state.activeProcessedFilename = data.activeProcessedFilename;
                    ImageLab.state.activeWidth = data.activeWidth;
                    ImageLab.state.activeHeight = data.activeHeight;
                    ImageLab.state.activeExtension = data.activeExtension;
                    ImageLab.state.activeSize = data.activeSize;

                    ImageLab.syncActiveFileUI();
                    
                    // Switch to Editor Canvas
                    if (document.getElementById('nav-editor')) {
                        document.getElementById('nav-editor').click();
                    }

                    alert(`Project "${res.data.project_name}" loaded successfully into workspace!`);
                } else {
                    alert('Failed to load project: ' + res.message);
                }
            });
    },

    deleteProject(projectId) {
        if (!confirm('Are you sure you want to delete this saved project?')) return;

        const params = new URLSearchParams();
        params.append('action', 'delete');
        params.append('id', projectId);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/saas/projects.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.loadProjects();
            } else {
                alert('Failed to delete project: ' + res.message);
            }
        });
    },

    loadDeveloperKeys() {
        fetch(`${ImageLabBaseUrl}/api/saas/keys.php`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    this.state.keys = res.data;
                    const tbody = document.getElementById('dev-keys-tbody');
                    if (tbody) {
                        tbody.innerHTML = '';
                        if (res.data.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No API keys created yet.</td></tr>';
                        }
                        res.data.forEach(k => {
                            const lastUsed = k.last_used_at ? k.last_used_at : 'Never';
                            tbody.innerHTML += `
                                <tr>
                                    <td><span class="fw-semibold">${k.name}</span></td>
                                    <td><code>${k.api_key}</code></td>
                                    <td><span class="badge ${k.status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">${k.status}</span></td>
                                    <td>${lastUsed}</td>
                                    <td class="text-end">
                                        ${k.status === 'active' ? `<button class="btn btn-sm btn-outline-danger py-1" onclick="ImageLabSaaS.revokeKey(${k.id})">Revoke</button>` : ''}
                                    </td>
                                </tr>
                            `;
                        });
                    }
                }
            });
    },

    createAPIKey() {
        const name = prompt("Enter a label name for the new API Key:", "Production Server Link");
        if (!name) return;

        const params = new URLSearchParams();
        params.append('action', 'generate');
        params.append('name', name);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/saas/keys.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert('API Key created: ' + res.api_key);
                this.loadDeveloperKeys();
            } else {
                alert('Failed to generate key: ' + res.message);
            }
        });
    },

    revokeKey(keyId) {
        if (!confirm('Are you sure you want to revoke this API key? This cannot be undone.')) return;

        const params = new URLSearchParams();
        params.append('action', 'revoke');
        params.append('id', keyId);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/saas/keys.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.loadDeveloperKeys();
            } else {
                alert('Failed to revoke API key.');
            }
        });
    },

    loadBilling() {
        // Fetch active transactions and invoices
        // We'll simulate fetching transactions / invoices
    },

    upgradePlan(plan) {
        if (!this.state.authenticated) {
            this.showAuthModal('login');
            return;
        }

        if (!confirm(`Upgrade subscription to ${plan.toUpperCase()} plan?`)) return;

        const params = new URLSearchParams();
        params.append('action', 'checkout');
        params.append('plan', plan);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/saas/paypal.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert(`Thank you! Payment approved. Subscription upgraded to ${plan.toUpperCase()} (TX: ${res.transaction_id}).`);
                location.reload();
            } else {
                alert('Payment failed: ' + res.message);
            }
        });
    },

    cancelSubscription() {
        if (!confirm('Are you sure you want to cancel your paid plan? You will lose access to higher limits immediately.')) return;

        const params = new URLSearchParams();
        params.append('action', 'cancel');
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/saas/paypal.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert(res.message);
                location.reload();
            } else {
                alert('Failed to cancel subscription: ' + res.message);
            }
        });
    },

    loadAdminPanel() {
        // Load summary
        fetch(`${ImageLabBaseUrl}/api/saas/admin.php?action=summary`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    document.getElementById('ad-total-users').textContent = res.data.users;
                    document.getElementById('ad-total-revenue').textContent = '$' + res.data.revenue;
                    document.getElementById('ad-total-storage').textContent = (res.data.storage / (1024 * 1024)).toFixed(1) + ' MB';
                    document.getElementById('ad-total-ai').textContent = res.data.ai_requests;
                }
            });

        // Load users list
        fetch(`${ImageLabBaseUrl}/api/saas/admin.php?action=users`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    this.state.users = res.data;
                    const tbody = document.getElementById('ad-users-tbody');
                    if (tbody) {
                        tbody.innerHTML = '';
                        res.data.forEach(u => {
                            const verified = u.email_verified ? '<span class="text-success"><i class="fa-solid fa-check"></i></span>' : '<span class="text-muted"><i class="fa-solid fa-minus"></i></span>';
                            tbody.innerHTML += `
                                <tr>
                                    <td>${u.id}</td>
                                    <td><span class="fw-semibold">${u.name}</span></td>
                                    <td>${u.email}</td>
                                    <td><span class="badge bg-light text-dark border">${u.role.toUpperCase()}</span></td>
                                    <td><span class="badge bg-primary-subtle text-primary">${(u.plan || 'Free').toUpperCase()}</span></td>
                                    <td>${verified}</td>
                                    <td class="text-end">
                                        <select class="form-select form-select-sm d-inline-block w-auto me-2" onchange="ImageLabSaaS.adminUpdateRole(${u.id}, this.value)">
                                            <option value="user" ${u.role === 'user' ? 'selected' : ''}>User</option>
                                            <option value="premium" ${u.role === 'premium' ? 'selected' : ''}>Premium</option>
                                            <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Admin</option>
                                        </select>
                                        <button class="btn btn-sm btn-outline-danger py-1" onclick="ImageLabSaaS.adminDeleteUser(${u.id})">Delete</button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                }
            });

        // Draw Admin Analytics Graphs
        fetch(`${ImageLabBaseUrl}/api/saas/admin.php?action=analytics`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    this.drawCharts(res.data);
                }
            });
    },

    adminUpdateRole(userId, role) {
        const params = new URLSearchParams();
        params.append('action', 'update_role');
        params.append('user_id', userId);
        params.append('role', role);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/saas/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert('User role updated successfully.');
                this.loadAdminPanel();
            } else {
                alert('Failed to update role: ' + res.message);
            }
        });
    },

    adminDeleteUser(userId) {
        if (!confirm('Are you sure you want to permanently delete this user account? This cannot be undone.')) return;

        const params = new URLSearchParams();
        params.append('action', 'delete_user');
        params.append('user_id', userId);
        params.append('csrf_token', this.state.csrfToken);

        fetch(`${ImageLabBaseUrl}/api/saas/admin.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert('User deleted.');
                this.loadAdminPanel();
            } else {
                alert('Failed to delete user: ' + res.message);
            }
        });
    },

    drawCharts(data) {
        // Line chart for active users
        const ctxUsers = document.getElementById('ad-users-chart');
        if (ctxUsers) {
            new Chart(ctxUsers, {
                type: 'line',
                data: {
                    labels: data.active_users.map(d => d.date),
                    datasets: [{
                        label: 'Daily Active Logins',
                        data: data.active_users.map(d => d.active_users),
                        borderColor: '#5e3bee',
                        tension: 0.3,
                        fill: false
                    }]
                },
                options: { responsive: true }
            });
        }

        // Bar chart for monthly revenue
        const ctxRev = document.getElementById('ad-revenue-chart');
        if (ctxRev) {
            new Chart(ctxRev, {
                type: 'bar',
                data: {
                    labels: data.revenue.map(r => r.month),
                    datasets: [{
                        label: 'Monthly Paid Billing ($)',
                        data: data.revenue.map(r => r.revenue),
                        backgroundColor: '#10b981'
                    }]
                },
                options: { responsive: true }
            });
        }

        // Pie chart for feature actions
        const ctxFeat = document.getElementById('ad-features-chart');
        if (ctxFeat) {
            new Chart(ctxFeat, {
                type: 'pie',
                data: {
                    labels: data.features.map(f => f.action.toUpperCase()),
                    datasets: [{
                        data: data.features.map(f => f.count),
                        backgroundColor: ['#5e3bee', '#10b981', '#f59e0b', '#3b82f6', '#ef4444', '#ec4899']
                    }]
                },
                options: { responsive: true }
            });
        }
    }
};

// Bind on page load
document.addEventListener('DOMContentLoaded', () => {
    ImageLabSaaS.init();
});
