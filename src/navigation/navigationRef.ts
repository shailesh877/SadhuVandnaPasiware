import { createNavigationContainerRef } from '@react-navigation/native';
import { RootStackParamList } from './types';

export const navigationRef = createNavigationContainerRef<RootStackParamList>();

/**
 * Robust navigation function that waits for the navigator to be ready.
 * Useful for navigating from services (Notifications, Call Listeners) during app boot.
 */
export async function navigateWithRetry(name: keyof RootStackParamList, params?: any, retries = 5) {
  if (navigationRef.isReady()) {
    navigationRef.navigate(name as any, params);
  } else {
    if (retries > 0) {
      console.log(`[Navigation] Not ready, retrying... (${retries} left)`);
      setTimeout(() => navigateWithRetry(name, params, retries - 1), 500);
    } else {
      console.error('[Navigation] Failed to navigate: Navigator not initialized after retries.');
    }
  }
}

// Legacy helper for simple cases
export function navigate(name: keyof RootStackParamList, params?: any) {
  if (navigationRef.isReady()) {
    navigationRef.navigate(name as any, params);
  } else {
    console.warn('[Navigation] Ref not ready, use navigateWithRetry if this happens during boot.');
  }
}

export function goBack() {
  if (navigationRef.isReady()) {
    navigationRef.goBack();
  } else {
    console.warn('[Navigation] Ref not ready for goBack');
  }
}
