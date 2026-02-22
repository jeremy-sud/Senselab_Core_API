<?php

$file = 'app/Services/PermissionService.php';
$content = file_get_contents($file);

$content = str_replace('public function userHasAnyPermission(Usuario $user, array $permissionSlugs): bool', "/**\n     * @param array<int, string> \$permissionSlugs\n     */\n    public function userHasAnyPermission(Usuario \$user, array \$permissionSlugs): bool", $content);
$content = str_replace('public function userHasAllPermissions(Usuario $user, array $permissionSlugs): bool', "/**\n     * @param array<int, string> \$permissionSlugs\n     */\n    public function userHasAllPermissions(Usuario \$user, array \$permissionSlugs): bool", $content);
$content = str_replace('public function getUserPermissions(Usuario $user): array', "/**\n     * @return array<int, string>\n     */\n    public function getUserPermissions(Usuario \$user): array", $content);
$content = str_replace('public function getRolePermissions(Rol $role): array', "/**\n     * @return array<int, string>\n     */\n    public function getRolePermissions(Rol \$role): array", $content);
$content = str_replace('public function assignPermissionsToRole(Rol $role, array $permisoIds): void', "/**\n     * @param array<int, int> \$permisoIds\n     */\n    public function assignPermissionsToRole(Rol \$role, array \$permisoIds): void", $content);
$content = str_replace('public function assignRolesToUser(Usuario $user, array $roleIds): void', "/**\n     * @param array<int, int> \$roleIds\n     */\n    public function assignRolesToUser(Usuario \$user, array \$roleIds): void", $content);
$content = str_replace('public function getCacheStats(): array', "/**\n     * @return array<string, mixed>\n     */\n    public function getCacheStats(): array", $content);

file_put_contents($file, $content);
