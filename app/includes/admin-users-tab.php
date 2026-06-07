<?php
$adminUsersList = AdminUserService::listUsers();
$adminUsersEditId = intval($_GET['edit_user'] ?? 0);
$adminUsersEdit = $adminUsersEditId > 0 ? AdminUserService::getUser($adminUsersEditId) : null;
$permissionGroups = AdminPermissionRegistry::groups();
$currentAdminId = intval(AdminUserService::current()['id'] ?? 0);
?>
<div class="tab-pane fade" id="tab-users" role="tabpanel" aria-labelledby="tab-users-nav" data-admin-perm="users">
    <div class="admin-card mb-3">
        <h5 class="fw-bold mb-2 font-montserrat text-navy">
            <i class="bi bi-people-fill me-2 text-danger"></i>Gestión de usuarios
        </h5>
        <p class="text-muted small mb-0">
            Cree cuentas con acceso limitado al panel. Marque las secciones del menú que cada usuario puede administrar.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="admin-card">
                <h6 class="fw-bold text-navy mb-3">Usuarios registrados</h6>
                <?php if ($adminUsersList === []): ?>
                    <p class="text-muted small mb-0">No hay usuarios en la base de datos. El acceso legacy (<code><?php echo esc(ADMIN_USER); ?></code>) sigue activo como super administrador.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adminUsersList as $u): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc($u['display_name']); ?></strong>
                                            <div class="small text-muted"><?php echo esc($u['username']); ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($u['is_super_admin'])): ?>
                                                <span class="badge bg-danger">Super admin</span>
                                            <?php else: ?>
                                                <span class="badge admin-table-badge"><?php echo count($u['permissions']); ?> permisos</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($u['is_active'])): ?>
                                                <span class="badge bg-success-subtle text-success border">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="?tab=users&amp;edit_user=<?php echo intval($u['id']); ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="admin-card">
                <h6 class="fw-bold text-navy mb-3">
                    <?php echo $adminUsersEdit ? 'Editar usuario' : 'Nuevo usuario'; ?>
                </h6>

                <form method="POST" action="?tab=users">
                    <input type="hidden" name="action" value="save_admin_user">
                    <input type="hidden" name="user_id" value="<?php echo $adminUsersEdit ? intval($adminUsersEdit['id']) : 0; ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre de usuario</label>
                            <input type="text" name="username" class="form-control form-control-premium" required
                                   pattern="[A-Za-z0-9._-]{3,40}"
                                   value="<?php echo esc($adminUsersEdit['username'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre para mostrar</label>
                            <input type="text" name="display_name" class="form-control form-control-premium"
                                   value="<?php echo esc($adminUsersEdit['display_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contraseña <?php echo $adminUsersEdit ? '(dejar vacío para no cambiar)' : ''; ?></label>
                            <input type="password" name="password" class="form-control form-control-premium" autocomplete="new-password"
                                   <?php echo $adminUsersEdit ? '' : 'required minlength="6"'; ?>>
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_super_admin" id="user_is_super" value="1"
                                    <?php echo !empty($adminUsersEdit['is_super_admin']) ? 'checked' : ''; ?>
                                    onchange="toggleUserPermissionsPanel(this)">
                                <label class="form-check-label" for="user_is_super">Super administrador (acceso total)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="user_is_active" value="1"
                                    <?php echo !$adminUsersEdit || !empty($adminUsersEdit['is_active']) ? 'checked' : ''; ?>
                                    <?php echo ($adminUsersEdit && intval($adminUsersEdit['id']) === $currentAdminId) ? 'disabled' : ''; ?>>
                                <label class="form-check-label" for="user_is_active">Activo</label>
                                <?php if ($adminUsersEdit && intval($adminUsersEdit['id']) === $currentAdminId): ?>
                                    <input type="hidden" name="is_active" value="1">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="user-permissions-panel" class="border rounded p-3 bg-light mb-3" style="<?php echo !empty($adminUsersEdit['is_super_admin']) ? 'display:none;' : ''; ?>">
                        <p class="small text-muted mb-3">Seleccione las secciones del menú admin que este usuario puede ver y editar:</p>
                        <?php foreach ($permissionGroups as $groupId => $group): ?>
                            <?php if ($groupId === 'main' && isset($group['permissions']['users'])): ?>
                                <?php
                                $groupPerms = $group['permissions'];
                                unset($groupPerms['users']);
                                if ($groupPerms === []) {
                                    continue;
                                }
                                ?>
                            <?php else: ?>
                                <?php $groupPerms = $group['permissions']; ?>
                            <?php endif; ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong class="small text-uppercase text-navy-light"><?php echo esc($group['label']); ?></strong>
                                    <button type="button" class="btn btn-link btn-sm p-0" onclick="togglePermissionGroup('<?php echo esc($groupId); ?>', true)">Todos</button>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="togglePermissionGroup('<?php echo esc($groupId); ?>', false)">Ninguno</button>
                                </div>
                                <div class="row g-2" data-perm-group="<?php echo esc($groupId); ?>">
                                    <?php foreach ($groupPerms as $permKey => $permLabel): ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input perm-checkbox perm-<?php echo esc($groupId); ?>"
                                                       type="checkbox" name="permissions[]"
                                                       id="perm_<?php echo esc($permKey); ?>"
                                                       value="<?php echo esc($permKey); ?>"
                                                    <?php
                                                    $checked = $adminUsersEdit
                                                        && empty($adminUsersEdit['is_super_admin'])
                                                        && in_array($permKey, $adminUsersEdit['permissions'], true);
                                                    echo $checked ? 'checked' : '';
                                                    ?>>
                                                <label class="form-check-label small" for="perm_<?php echo esc($permKey); ?>">
                                                    <?php echo esc($permLabel); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (AdminUserService::isSuperAdmin()): ?>
                            <div class="border-top pt-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input perm-checkbox perm-main" type="checkbox" name="permissions[]"
                                           id="perm_users" value="users"
                                        <?php
                                        echo ($adminUsersEdit && in_array('users', $adminUsersEdit['permissions'], true)) ? 'checked' : '';
                                        ?>>
                                    <label class="form-check-label small" for="perm_users">Gestión de usuarios (solo administradores de confianza)</label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button type="submit" class="btn btn-premium">
                            <i class="bi bi-save me-1"></i> Guardar usuario
                        </button>
                        <?php if ($adminUsersEdit): ?>
                            <a href="?tab=users" class="btn btn-outline-secondary">Cancelar edición</a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($adminUsersEdit && intval($adminUsersEdit['id']) !== $currentAdminId): ?>
                    <form method="POST" action="?tab=users" class="mt-3"
                          onsubmit="return confirm('¿Eliminar este usuario permanentemente?');">
                        <input type="hidden" name="action" value="delete_admin_user">
                        <input type="hidden" name="user_id" value="<?php echo intval($adminUsersEdit['id']); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Eliminar usuario
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleUserPermissionsPanel(checkbox) {
    const panel = document.getElementById('user-permissions-panel');
    if (!panel) return;
    panel.style.display = checkbox.checked ? 'none' : '';
    panel.querySelectorAll('.perm-checkbox').forEach(el => {
        el.disabled = checkbox.checked;
    });
}
function togglePermissionGroup(groupId, checked) {
    document.querySelectorAll('.perm-' + groupId).forEach(el => {
        if (!el.disabled) el.checked = checked;
    });
}
document.addEventListener('DOMContentLoaded', function () {
    const superCb = document.getElementById('user_is_super');
    if (superCb) toggleUserPermissionsPanel(superCb);
});
</script>
