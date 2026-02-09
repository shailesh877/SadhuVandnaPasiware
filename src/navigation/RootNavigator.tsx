import React, { useEffect, useState } from 'react';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { ActivityIndicator, View } from 'react-native';
import { updateServerToken } from '../services/NotificationService';

import LoginScreen from '../screens/auth/LoginScreen';
import RegisterScreen from '../screens/auth/RegisterScreen';
import ForgotPasswordScreen from '../screens/auth/ForgotPasswordScreen';
import HomeScreen from '../screens/feed/HomeScreen';
import MarriageScreen from '../screens/marriage/MarriageScreen';
import ProfileScreen from '../screens/profile/ProfileScreen';
import MarriageDetailScreen from '../screens/marriage/MarriageDetailScreen';
import ChatScreen from '../screens/chat/ChatScreen';
import ChatListScreen from '../screens/chat/ChatListScreen';
import RequestScreen from '../screens/marriage/RequestScreen';
import NewsDetailScreen from '../screens/news/NewsDetailScreen';
import NewsScreen from '../screens/news/NewsScreen';
import PostDetailScreen from '../screens/post/PostDetailScreen';
import CreatePostScreen from '../screens/post/CreatePostScreen';
import CreateStoryScreen from '../screens/feed/CreateStoryScreen';
import EditProfileScreen from '../screens/profile/EditProfileScreen';
import PublicProfileScreen from '../screens/profile/PublicProfileScreen';
import MyPostsScreen from '../screens/profile/MyPostsScreen';
import CommentsScreen from '../screens/feed/CommentsScreen';
import GalleryScreen from '../screens/profile/GalleryScreen';
import StoryViewerScreen from '../screens/feed/StoryViewerScreen';
import NotificationScreen from '../screens/feed/NotificationScreen';
import ApplyJobScreen from '../screens/jobs/ApplyJobScreen';
import SettingsScreen from '../screens/settings/SettingsScreen';
import ChangePasswordScreen from '../screens/settings/ChangePasswordScreen';
import CreateMarriageProfileScreen from '../screens/marriage/CreateMarriageProfileScreen';
import TempleScreen from '../screens/temple/TempleScreen';
import BranchScreen from '../screens/temple/BranchScreen';
import JobScreen from '../screens/jobs/JobScreen';
import FamilyScreen from '../screens/family/FamilyScreen';
import ShokSanvedanaScreen from '../screens/shok/ShokSanvedanaScreen';
import ConnectedScreen from '../screens/marriage/ConnectedScreen';
import AgoraCallScreen from '../screens/call/AgoraCallScreen';
import FestivalPosterScreen from '../screens/feed/FestivalPosterScreen';
import GlobalCallListener from '../components/GlobalCallListener';

const Stack = createNativeStackNavigator<RootStackParamList>();
const AuthStack = createNativeStackNavigator<AuthStackParamList>();
const Tab = createBottomTabNavigator();

export type AuthStackParamList = {
  Login: undefined;
  Register: undefined;
  ForgotPassword: undefined;
};

export type RootStackParamList = {
  Auth: undefined;
  MainTabs: undefined;
  PublicProfile: { userId: string };
  MarriageDetail: { id: string };
  Chat: { receiver: any };
  NewsDetail: { id: string };
  PostDetail: { id: string };
  Comments: { postId: string };
  CreatePost: undefined;
  CreateStory: undefined;
  StoryViewer: { stories: any[], initialIndex: number };
  EditProfile: undefined;
  MyPosts: undefined;
  Gallery: undefined;
  CreateMarriageProfile: undefined;
  Temples: undefined;
  Branches: { templeId: string };
  Jobs: undefined;
  Family: undefined;
  Profile: undefined;
  Requests: undefined;
  ShokSanvedana: undefined;
  Connected: undefined;
  Notifications: undefined;
  ApplyJob: { jobId: string };
  Settings: undefined;
  ChangePassword: undefined;
  AgoraCall: { channelId: string, isVideo: boolean, isCaller: boolean, otherUserId: string };
  FestivalPoster: undefined;
};

/* ------------------ TAB NAVIGATOR ------------------ */
function MainTabNavigator() {
  return (
    <Tab.Navigator
      initialRouteName="Home"
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarActiveTintColor: '#ea580c',
        tabBarInactiveTintColor: 'gray',
        tabBarIcon: ({ focused, color, size }) => {
          let iconName: any;
          if (route.name === 'Home') iconName = focused ? 'home' : 'home-outline';
          else if (route.name === 'News') iconName = focused ? 'newspaper' : 'newspaper-outline';
          else if (route.name === 'Marriage') iconName = focused ? 'heart' : 'heart-outline';
          else if (route.name === 'Chats') iconName = focused ? 'chatbubbles' : 'chatbubbles-outline';
          return <Ionicons name={iconName} size={size} color={color} />;
        },
      })}
    >
      <Tab.Screen name="Home" component={HomeScreen} />
      <Tab.Screen name="News" component={NewsScreen} />
      <Tab.Screen name="Marriage" component={MarriageScreen} />
      <Tab.Screen name="Chats" component={ChatListScreen} />
    </Tab.Navigator>
  );
}

/* ------------------ AUTH NAVIGATOR ------------------ */
function AuthNavigator() {
  return (
    <AuthStack.Navigator screenOptions={{ headerShown: false }}>
      <AuthStack.Screen name="Login" component={LoginScreen} />
      <AuthStack.Screen name="Register" component={RegisterScreen} />
      <AuthStack.Screen name="ForgotPassword" component={ForgotPasswordScreen} />
    </AuthStack.Navigator>
  );
}

/* ------------------ ROOT NAVIGATOR ------------------ */
const RootNavigator = () => {
  const [initialRoute, setInitialRoute] = useState<keyof RootStackParamList | null>(null);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const userStr = await AsyncStorage.getItem('user');
      if (userStr) {
        const user = JSON.parse(userStr);
        setInitialRoute('MainTabs');
        updateServerToken(user.id);
      } else {
        setInitialRoute('Auth');
      }
    } catch {
      setInitialRoute('Auth');
    }
  };

  if (!initialRoute) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator size="large" color="#ea580c" />
      </View>
    );
  }

  return (
    <View style={{ flex: 1 }}>
      <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName={initialRoute}>
        <Stack.Screen name="Auth" component={AuthNavigator} />
        <Stack.Screen name="MainTabs" component={MainTabNavigator} />
        <Stack.Screen name="PublicProfile" component={PublicProfileScreen} />
        <Stack.Screen name="MarriageDetail" component={MarriageDetailScreen} />
        <Stack.Screen name="Chat" component={ChatScreen} />
        <Stack.Screen name="NewsDetail" component={NewsDetailScreen} />
        <Stack.Screen name="PostDetail" component={PostDetailScreen} />
        <Stack.Screen name="Comments" component={CommentsScreen} options={{ presentation: 'modal' }} />
        <Stack.Screen name="CreatePost" component={CreatePostScreen} />
        <Stack.Screen name="CreateStory" component={CreateStoryScreen} />
        <Stack.Screen name="StoryViewer" component={StoryViewerScreen} />
        <Stack.Screen name="EditProfile" component={EditProfileScreen} />
        <Stack.Screen name="MyPosts" component={MyPostsScreen} />
        <Stack.Screen name="Gallery" component={GalleryScreen} />
        <Stack.Screen name="CreateMarriageProfile" component={CreateMarriageProfileScreen} />
        <Stack.Screen name="Temples" component={TempleScreen} />
        <Stack.Screen name="Branches" component={BranchScreen} />
        <Stack.Screen name="Jobs" component={JobScreen} />
        <Stack.Screen name="Family" component={FamilyScreen} />
        <Stack.Screen name="Profile" component={ProfileScreen} />
        <Stack.Screen name="Requests" component={RequestScreen} />
        <Stack.Screen name="ShokSanvedana" component={ShokSanvedanaScreen} />
        <Stack.Screen name="Connected" component={ConnectedScreen} />
        <Stack.Screen name="Notifications" component={NotificationScreen} />
        <Stack.Screen name="ApplyJob" component={ApplyJobScreen} />
        <Stack.Screen name="Settings" component={SettingsScreen} />
        <Stack.Screen name="ChangePassword" component={ChangePasswordScreen} />
        <Stack.Screen name="FestivalPoster" component={FestivalPosterScreen} />
        <Stack.Screen name="AgoraCall" component={AgoraCallScreen} options={{ headerShown: false }} />
      </Stack.Navigator>
      <GlobalCallListener />
    </View>
  );
};

export default RootNavigator;
