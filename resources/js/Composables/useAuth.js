import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAuth() {
  const page = usePage();
  
  const user = computed(() => page.props.auth?.user || null);
  const institution = computed(() => page.props.auth?.institution || null);
  const isAuthenticated = computed(() => !!user.value);
  
  const hasRole = (role) => {
    return user.value?.role === role;
  };
  
  const hasPermission = (permission) => {
    if (!user.value?.permissions) return false;
    return user.value.permissions.includes(permission);
  };
  
  const can = (permission) => {
    return hasPermission(permission);
  };
  
  return {
    user,
    institution,
    isAuthenticated,
    hasRole,
    hasPermission,
    can
  };
}
