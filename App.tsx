import { StatusBar } from 'expo-status-bar';
import React, { useState, useEffect } from 'react';
import { View, ActivityIndicator, Text } from 'react-native';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import './global.css';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { NavigationContainer } from '@react-navigation/native';
import RootNavigator from './src/navigation/RootNavigator';
import { navigationRef } from './src/navigation/navigationRef';
import { RootStackParamList } from './src/navigation/types';
import { updateServerToken } from './src/services/NotificationService';
import GlobalCallListener from './src/components/GlobalCallListener';
import api from './src/services/api';

import { GestureHandlerRootView } from 'react-native-gesture-handler';

import { LanguageProvider } from './src/context/LanguageContext';
import { FontProvider } from './src/context/FontContext';
import mobileAds from 'react-native-google-mobile-ads';
// import { usePreventScreenCapture } from 'expo-screen-capture';
import { Audio } from 'expo-av';

mobileAds()
  .initialize()
  .then(adapterStatuses => {
    console.log("[AdMob] Initialization complete!", adapterStatuses);
  });



export default function App() {
  const [initialRoute, setInitialRoute] = useState<keyof RootStackParamList | null>(null);

  useEffect(() => {
    console.log("[App] Initializing...");
    setupAudio();
    checkAuth();
  }, []);

  const setupAudio = async () => {
    try {
      await Audio.setAudioModeAsync({
        allowsRecordingIOS: true,
        playsInSilentModeIOS: true,
        staysActiveInBackground: false,
        interruptionModeIOS: 2, // InterruptionModeIOS.MixWithOthers (Important for video+audio)
        shouldDuckAndroid: true,
        interruptionModeAndroid: 2, // InterruptionModeAndroid.MixWithOthers
        playThroughEarpieceAndroid: false,
      });
    } catch (e) {
      console.error("[Audio] Setup Error:", e);
    }
  };

  // usePreventScreenCapture();

  const checkAuth = async () => {
    try {
      const u = await AsyncStorage.getItem('user');
      if (u) {
        const user = JSON.parse(u);
        setInitialRoute('MainTabs');
        updateServerToken(user.id);

        // Start Global Online Heartbeat 
        // Updates every 60 seconds while app is open
        setInterval(async () => {
          try {
            const payload = { user_id: user.id };
            await api.post('update_app_online.php', payload);
          } catch (e) { }
        }, 60000);

      } else {
        setInitialRoute('Auth');
      }
    } catch (e) {
      setInitialRoute('Auth');
    }
  };

  if (!initialRoute) {
    return (
      <View style={{ flex: 1, backgroundColor: 'white', justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color="#ea580c" />
      </View>
    );
  }

  const linking = {
    prefixes: ['sadhuvandna://', 'https://www.sadhuvandna.co.in', 'https://sadhuvandna.co.in'],
    config: {
      screens: {
        MainTabs: {
          screens: {
            Home: 'home',
            Reels: 'reels',
          }
        },
        PostDetail: {
          path: 'view_post.php',
          parse: {
            postId: (id: string) => id,
          },
        },
        NewsDetail: {
          path: 'view_news.php',
          parse: {
            newsId: (id: string) => id,
          },
        },
        JoinGroup: {
          path: 'join',
          parse: {
            inviteCode: (code: string) => code,
          },
        },
      },
    },
  };

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <FontProvider>
          <LanguageProvider>
            <NavigationContainer ref={navigationRef} linking={linking}>
              <StatusBar style="dark" />
              <RootNavigator initialRoute={initialRoute} />
              <GlobalCallListener />
            </NavigationContainer>
          </LanguageProvider>
        </FontProvider>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}