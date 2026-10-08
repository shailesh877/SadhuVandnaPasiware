import 'react-native-gesture-handler';
import { enableScreens } from 'react-native-screens';
// Temporarily disabling native screens to debug context isolation in RN 0.81
enableScreens(true);
import { registerRootComponent } from 'expo';
import { BackHandler } from 'react-native';
import React from 'react';

// Polyfill: BackHandler.removeEventListener was removed in newer RN versions.
// Some older libraries still call it, causing crashes. This no-ops it safely.
if (!(BackHandler as any).removeEventListener) {
  (BackHandler as any).removeEventListener = () => {};
}

// Global Text Component Override via module mutation
const ReactNative = require('react-native');
const OriginalText = ReactNative.Text;

const fontListeners = new Set<any>();
(global as any).notifyFontChange = (font: string | undefined) => {
  fontListeners.forEach(listener => {
    try {
      listener(font);
    } catch (e) {
      console.warn("Font listener update failed", e);
    }
  });
};

const CustomText = React.forwardRef((props: any, ref: any) => {
  const [activeFont, setActiveFont] = React.useState((global as any).activeFontFamily);

  React.useEffect(() => {
    const listener = (font: string | undefined) => {
      setActiveFont(font);
    };
    fontListeners.add(listener);
    return () => {
      fontListeners.delete(listener);
    };
  }, []);

  const mergedStyle = activeFont ? [{ fontFamily: activeFont }, props.style] : props.style;
  return React.createElement(OriginalText, {
    ...props,
    ref,
    style: mergedStyle
  });
});

Object.assign(CustomText, OriginalText);
Object.defineProperty(ReactNative, 'Text', {
  get: () => CustomText,
  configurable: true,
  enumerable: true
});

import App from './App';


// registerRootComponent calls AppRegistry.registerComponent('main', () => App);
// It also ensures that whether you load the app in Expo Go or in a native build,
// the environment is set up appropriately
registerRootComponent(App);
