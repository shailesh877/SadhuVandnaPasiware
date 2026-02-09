import { StatusBar } from 'expo-status-bar';
import React from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import './global.css';

import { NavigationContainer } from '@react-navigation/native';
import RootNavigator from './src/navigation/RootNavigator';

import { GestureHandlerRootView } from 'react-native-gesture-handler';

import { LanguageProvider } from './src/context/LanguageContext';

export default function App() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <LanguageProvider>
        <SafeAreaProvider>
          <NavigationContainer>
            <RootNavigator />
          </NavigationContainer>
          <StatusBar style="auto" />
        </SafeAreaProvider>
      </LanguageProvider>
    </GestureHandlerRootView>
  );
}