export type MainTabParamList = {
  Home: undefined;
  Reels: undefined;
  News: undefined;
  Marriage: undefined;
  Chats: undefined;
};

export type AuthStackParamList = {
  Login: undefined;
  Register: undefined;
  ForgotPassword: undefined;
};

export type RootStackParamList = {
  Auth: undefined;
  MainTabs: undefined;
  PublicProfile: { userId: string };
  FollowList: { userId: string, action: string, title: string };
  MarriageDetail: { profile: any };
  Chat: { receiver: any, platform: 'marriage' | 'community', isGroup?: boolean };
  NewsDetail: { news: any };
  PostDetail: { post?: any, postId?: string };
  Comments: { postId: string };
  CreatePost: { videoUri?: string, preSelectedMusic?: any, isReel?: boolean };
  CreateReel: undefined;
  CreateStory: undefined;
  StoryViewer: { stories: any[], initialIndex: number };
  EditProfile: undefined;
  MyPosts: undefined;
  Gallery: undefined;
  CreateMarriageProfile: { profile?: any };
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
  AgoraCall: { channelId: string, isVideo: boolean, isCaller: boolean, otherUserId: string, myProfileIdFallback?: number };
  FestivalPoster: undefined;
  Search: undefined;
  ReelsViewer: { startPostId: string, filter_user_id?: string, showBack?: boolean };
  SmartCard: undefined;
  CreateGroup: undefined;
  GroupSettings: { group: any };
  AddGroupMembers: { group: any, currentMembers: any[] };
  CreateNews: undefined;
  JoinGroup: { inviteCode: string };
  SupportChat: undefined;
};
