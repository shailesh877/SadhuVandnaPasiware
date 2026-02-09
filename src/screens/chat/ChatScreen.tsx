import React, { useEffect, useState, useRef } from 'react';
import {
    View,
    Text,
    TextInput,
    TouchableOpacity,
    FlatList,
    KeyboardAvoidingView,
    Platform,
    ActivityIndicator,
    Alert,
    Image,
    Linking,
    Keyboard,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import api, { API_BASE_URL } from '../../services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { useLanguage } from '../../context/LanguageContext';
import EmojiSelector from 'react-native-emoji-selector';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { RootStackParamList } from '../../navigation/RootNavigator';
import WebCallModal from '../../components/WebCallModal';

import { Video, ResizeMode } from 'expo-av';
import * as ImagePicker from 'expo-image-picker';

const BASE_URL_ROOT = API_BASE_URL.replace('/Api', '');
type Props = NativeStackScreenProps<RootStackParamList, 'Chat'>;
const PHOTO_URL = `${BASE_URL_ROOT}/uploads/photo/`;

const ChatScreen = ({ navigation, route }: any) => {
    const { receiver: rawReceiver } = route.params || {};
    const { t } = useLanguage();

    // Ensure receiver object is robust
    const receiver = rawReceiver
        ? {
            ...rawReceiver,
            id: rawReceiver.id || rawReceiver.profile_id || rawReceiver.user_id,
            // Fallback for names
            full_name: rawReceiver.full_name || rawReceiver.name || 'User',
        }
        : null;

    const [messages, setMessages] = useState<any[]>([]);
    const [text, setText] = useState('');
    const [myProfileId, setMyProfileId] = useState<string | null>(null);
    const [userId, setUserId] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [paymentStatus, setPaymentStatus] =
        useState<'checking' | 'paid' | 'unpaid'>('checking');
    const [paymentUrl, setPaymentUrl] = useState<string | null>(null);

    // Media Upload State
    const [selectedMedia, setSelectedMedia] = useState<any>(null); // { uri, type, mimeType }
    const [uploading, setUploading] = useState(false);

    const [isTyping, setIsTyping] = useState(false);
    const [isReceiverTyping, setIsReceiverTyping] = useState(false);
    const [receiverOnline, setReceiverOnline] = useState(false);
    const [receiverLastSeen, setReceiverLastSeen] = useState<string | null>(null);

    // Emoji Picker State
    const [showEmojiPicker, setShowEmojiPicker] = useState(false);

    // Block User State
    const [isBlocked, setIsBlocked] = useState(false);
    const [showMenu, setShowMenu] = useState(false);

    // Web Call State
    const [webCallUrl, setWebCallUrl] = useState<string | null>(null);

    const flatListRef = useRef<FlatList>(null);
    const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const isMountedRef = useRef(true);

    useEffect(() => {
        init();
        return () => {
            isMountedRef.current = false;
        };
    }, []);

    useEffect(() => {
        const interval = setInterval(() => {
            if (paymentStatus === 'paid' && myProfileId) {
                fetchMessages();
                fetchUserStatus();
                updateMyOnlineStatus();
                checkBlockStatus(); // Poll for block status
            }
        }, 2000);

        return () => clearInterval(interval);
    }, [paymentStatus, myProfileId]);

    const init = async () => {
        try {
            const u = await AsyncStorage.getItem('user');
            if (u) {
                const user = JSON.parse(u);
                setUserId(user.id);
                checkPayment(user.id);
                updateMyOnlineStatus(user.id);
            }
        } catch (e) {
            console.error(e);
        }
    };

    const updateMyOnlineStatus = async (uid?: string) => {
        try {
            const id = uid || userId;
            if (id) {
                await api.post('/update_app_online.php', { user_id: id });
            }
        } catch { }
    };

    const checkPayment = async (uId: string) => {
        if (!receiver?.id) return;
        try {
            const res = await api.get(
                `/check_chat_payment.php?user_id=${uId}&receiver_id=${receiver.id}`
            );

            if (res.data.status === 'success') {
                setMyProfileId(res.data.my_profile_id);

                // Check block status right away
                // using the ID from response as state update might be async
                checkBlockStatusInternal(res.data.my_profile_id);

                if (res.data.paid) {
                    setPaymentStatus('paid');
                    fetchMessages(res.data.my_profile_id);
                    fetchUserStatus(res.data.my_profile_id);
                } else {
                    setPaymentStatus('unpaid');
                    setPaymentUrl(
                        res.data.payment_url
                            ? `${BASE_URL_ROOT}/php/${res.data.payment_url}`
                            : null
                    );
                }
            } else {
                Alert.alert('Error', res.data.message || 'Payment check failed');
            }
        } catch {
            Alert.alert('Error', 'Network error while checking payment');
        } finally {
            setLoading(false);
        }
    };

    // Helper to check block status with immediate ID
    const checkBlockStatusInternal = async (mid: string) => {
        if (!mid || !receiver?.id) return;

        // Local Check first
        try {
            const storedStatus = await AsyncStorage.getItem(`blocked_${mid}_${receiver.id}`);
            if (storedStatus !== null) {
                setIsBlocked(storedStatus === 'true');
            }
        } catch (e) { }

        try {
            const formData = new FormData();
            formData.append('my_id', mid);
            formData.append('target_id', receiver.id);
            formData.append('action', 'check');

            const res = await api.post('/block_user.php', formData);
            if (res.data.status === 'success') {
                setIsBlocked(res.data.blocked);
                AsyncStorage.setItem(`blocked_${mid}_${receiver.id}`, String(res.data.blocked));
            }
        } catch (e) { }
    };

    const fetchMessages = async (pid: string | null = null) => {
        const currentId = pid || myProfileId;
        if (!currentId || !receiver?.id) return;

        try {
            const res = await api.get(
                `/get_chat_messages.php?my_profile_id=${currentId}&receiver_id=${receiver.id}`
            );
            if (res.data.status === 'success' && isMountedRef.current) {
                setMessages(res.data.data);
            }
        } catch { }
    };
    const fetchUserStatus = async (pid: string | null = null) => {
        const currentId = pid || myProfileId;
        if (!currentId || !receiver?.id) return;

        try {
            // Note: API now expects profile_id (receiver) and my_profile_id (me)
            const res = await api.get(
                `/get_chat_user_status.php?profile_id=${receiver.id}&my_profile_id=${currentId}`
            );
            if (res.data.status === 'success' && isMountedRef.current) {
                setReceiverOnline(res.data.online);
                setReceiverLastSeen(res.data.last_active);
                setIsReceiverTyping(res.data.is_typing);

                // Incoming call is now handled by GlobalCallListener

            }
        } catch (e) { console.log("Fetch Status Error", e); }
    };

    const handleTyping = (txt: string) => {
        setText(txt);
        if (!myProfileId) return;

        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }

        if (!isTyping) {
            setIsTyping(true);
            updateTypingStatus(true);
        }

        typingTimeoutRef.current = setTimeout(() => {
            setIsTyping(false);
            updateTypingStatus(false);
        }, 2000);
    };

    const updateTypingStatus = async (typing: boolean) => {
        if (!myProfileId || !receiver?.id) return;
        try {
            const formData = new FormData();
            formData.append('profile_id', myProfileId);
            formData.append('receiver_id', receiver.id);
            formData.append('is_typing', typing ? '1' : '0');
            await api.post('/update_chat_typing.php', formData);
        } catch { }
    };

    const pickMedia = async () => {
        setShowEmojiPicker(false);
        try {
            const result = await ImagePicker.launchImageLibraryAsync({
                mediaTypes: ImagePicker.MediaTypeOptions.All, // Images and Videos
                allowsEditing: false, // Videos often fail with editing on some versions, safer false for mixed
                quality: 0.8,
            });

            if (!result.canceled && result.assets && result.assets.length > 0) {
                const asset = result.assets[0];
                setSelectedMedia({
                    uri: asset.uri,
                    type: asset.type, // 'image' or 'video'
                    mimeType: asset.mimeType,
                    fileName: asset.fileName || 'upload'
                });
            }
        } catch (error) {
            Alert.alert("Error", "Failed to pick media");
        }
    };

    const cancelMedia = () => setSelectedMedia(null);

    const sendMessage = async () => {
        if ((!text.trim() && !selectedMedia) || !myProfileId || paymentStatus !== 'paid' || isBlocked) return;

        setShowEmojiPicker(false);

        const msgContent = text;
        const mediaToSend = selectedMedia;

        // Optimistic clear
        setText('');
        setSelectedMedia(null);
        updateTypingStatus(false);
        setUploading(!!mediaToSend);

        try {
            const formData = new FormData();
            formData.append('my_profile_id', myProfileId);
            formData.append('receiver_id', receiver.id);
            formData.append('message', msgContent); // Server handles empty message if file is present? API code says $msg = trim(...) but doesn't strictly fail if empty IF file is there? 
            // Actually API says: if(!$my || !$receiver){ error }. It doesn't check msg emptiness strictly if file is there. 
            // BUT: $msg = trim(...). If empty, it's empty string.
            // Let's ensure at least one exists. The check at top does that.

            if (mediaToSend) {
                const filename = mediaToSend.fileName || mediaToSend.uri.split('/').pop();
                const type = mediaToSend.mimeType || (mediaToSend.type === 'video' ? 'video/mp4' : 'image/jpeg');
                formData.append('attachment', {
                    uri: mediaToSend.uri,
                    name: filename,
                    type: type,
                } as any);
            }

            const res = await api.post('/send_chat_message.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (res.data.status === 'success') {
                fetchMessages();
            } else {
                Alert.alert('Error', res.data.message || 'Failed to send message');
                // Restore if failed? user experience trade-off. For now just alert.
            }
        } catch (e: any) {
            console.error("Send Error:", e);
            Alert.alert('Error', e.message || 'Network error');
        } finally {
            setUploading(false);
        }
    };

    const initiateCall = async (type: 'audio' | 'video') => {
        if (!myProfileId || !receiver?.id) return;
        if (isBlocked) {
            Alert.alert("Blocked", "You cannot call a blocked user.");
            return;
        }

        Alert.alert(
            `Start ${type === 'video' ? 'Video' : 'Audio'} Call`,
            "Are you sure?",
            [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Call",
                    onPress: async () => {
                        // 1. Generate Consistent Channel ID (Sort IDs)
                        const ids = [parseInt(myProfileId), parseInt(receiver.id)].sort((a, b) => a - b);
                        const channelId = `call_${ids[0]}_${ids[1]}`;

                        // 2. Signal the call to backend (so receiver knows)
                        try {
                            const fd = new FormData();
                            fd.append('caller_id', myProfileId);
                            fd.append('receiver_id', receiver.id);
                            fd.append('type', type);
                            fd.append('peer_id', channelId); // We use channelId as "peer_id" reference
                            await api.post('/initiate_call.php', fd);
                        } catch (e) { console.error("Signal Error:", e); }

                        // 3. Navigate
                        navigation.navigate('AgoraCall', {
                            channelId: channelId,
                            isVideo: (type === 'video'),
                            isCaller: true,
                            otherUserId: receiver.id
                        });
                    }
                }
            ]
        );
    };

    const checkBlockStatus = async () => {
        if (!myProfileId || !receiver?.id) return;

        // Check local storage first (fallback because server might not have check logic yet)
        try {
            const storedStatus = await AsyncStorage.getItem(`blocked_${myProfileId}_${receiver.id}`);
            if (storedStatus !== null) {
                setIsBlocked(storedStatus === 'true');
            }
        } catch (e) { }

        try {
            const formData = new FormData();
            formData.append('my_id', myProfileId);
            formData.append('target_id', receiver.id);
            formData.append('action', 'check');

            const res = await api.post('/block_user.php', formData);
            if (res.data.status === 'success') {
                setIsBlocked(res.data.blocked);
                // Sync storage
                AsyncStorage.setItem(`blocked_${myProfileId}_${receiver.id}`, String(res.data.blocked));
            }
        } catch (e) { console.log("Check Block Error", e); }
    };

    const toggleBlockUser = async () => {
        if (!myProfileId || !receiver?.id) return;

        setShowMenu(false); // Close menu

        const action = isBlocked ? 'unblock' : 'block';
        const confirmMsg = isBlocked
            ? "Are you sure you want to Unblock this user?"
            : "Are you sure you want to Block this user? They won't be able to message you.";

        Alert.alert(
            isBlocked ? "Unblock User" : "Block User",
            confirmMsg,
            [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Unblock",
                    style: isBlocked ? "default" : "destructive",
                    onPress: async () => {
                        try {
                            const formData = new FormData();
                            formData.append('my_id', myProfileId);
                            formData.append('target_id', receiver.id);
                            formData.append('action', action);

                            const res = await api.post('/block_user.php', formData);
                            if (res.data.status === 'success') {
                                const newState = !isBlocked;
                                setIsBlocked(newState);
                                AsyncStorage.setItem(`blocked_${myProfileId}_${receiver.id}`, String(newState));
                                Alert.alert("Success", isBlocked ? "User Unblocked" : "User Blocked");
                            } else {
                                Alert.alert("Error", res.data.message || "Action failed");
                            }
                        } catch (e) {
                            Alert.alert("Error", "Network error");
                        }
                    }
                }
            ]
        );
    };

    const handleDeleteMessage = async (msgId: string) => {
        Alert.alert("Delete Message", "Are you sure you want to delete this message?", [
            { text: "Cancel", style: "cancel" },
            {
                text: "Delete", style: "destructive", onPress: async () => {
                    try {
                        const fd = new FormData();
                        fd.append('message_id', msgId);
                        fd.append('my_profile_id', myProfileId!.toString());

                        const res = await api.post('/delete_chat_message.php', fd);
                        if (res.data.status === 'success') {
                            setMessages(prev => prev.filter(m => m.id !== msgId));
                        } else {
                            // Alert.alert("Error", res.data.message);
                        }
                    } catch (error) { console.error(error); }
                }
            }
        ]);
    };

    const handlePayNow = () => {
        if (paymentUrl) Linking.openURL(paymentUrl);
        else Alert.alert('Error', 'Payment URL not available');
    };

    const getProfileImage = (usr: any) => {
        if (!usr) return null; // Return null to trigger fallback in render

        // Check all possible keys
        const img = usr.photo || usr.profile_photo || usr.avatar || usr.image;

        if (!img) return null;
        if (img.startsWith('http')) return { uri: img };
        return { uri: `${PHOTO_URL}${encodeURIComponent(img)}` };
    };

    // Helper to render Avatar or Initials
    const renderAvatar = () => {
        const source = getProfileImage(receiver);

        if (source) {
            return (
                <Image
                    source={source}
                    className="w-11 h-11 rounded-full bg-gray-200 border-2 border-white shadow-sm"
                />
            );
        }

        // Fallback: Initials
        const initials = (receiver?.full_name || 'U').charAt(0).toUpperCase();
        return (
            <View className="w-11 h-11 rounded-full bg-orange-100 border-2 border-white shadow-sm items-center justify-center">
                <Text className="text-orange-600 font-bold text-lg">{initials}</Text>
            </View>
        );
    };


    if (loading || paymentStatus === 'checking') {
        return (
            <View className="flex-1 justify-center items-center bg-gray-50">
                <ActivityIndicator size="large" color="#ea580c" />
                <Text className="text-gray-400 text-xs mt-3">
                    {t('loading')}...
                </Text>
            </View>
        );
    }

    if (paymentStatus === 'unpaid') {
        return (
            <View className="flex-1 items-center justify-center bg-white p-6">
                <TouchableOpacity
                    onPress={handlePayNow}
                    className="bg-orange-600 px-6 py-3 rounded-xl"
                >
                    <Text className="text-white font-bold">Pay Now</Text>
                </TouchableOpacity>

                <TouchableOpacity
                    onPress={() => navigation.goBack()}
                    className="mt-6"
                >
                    <Ionicons name="close" size={26} color="gray" />
                </TouchableOpacity>
            </View>
        );
    }

    if (!receiver) return null;

    return (
        <View className="flex-1 bg-[#e5e7eb]">
            {/* Premium Header */}
            <View className="pt-12 pb-3 px-4 bg-white border-b border-gray-100 shadow-sm z-20 flex-row items-center">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3 p-2 bg-gray-50 rounded-full">
                    <Ionicons name="arrow-back" size={22} color="#1f2937" />
                </TouchableOpacity>
                <View className="relative">
                    {renderAvatar()}
                    {receiverOnline && (
                        <View className="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-white shadow-sm" />
                    )}
                </View>
                <View className="ml-3 flex-1 justify-center">
                    <Text className="font-bold text-lg text-gray-900 leading-tight" numberOfLines={1}>
                        {receiver.full_name}
                    </Text>
                    {isReceiverTyping ? (
                        <Text className="text-xs text-orange-600 font-bold tracking-wide animate-pulse">
                            Typing...
                        </Text>
                    ) : (
                        <Text className={`text-xs font-medium ${receiverOnline ? 'text-green-600' : 'text-gray-400'}`}>
                            {receiverOnline ? t('online') : (receiverLastSeen ? `Last seen ${receiverLastSeen}` : t('offline'))}
                        </Text>
                    )}
                </View>

                {/* Call Buttons */}
                <TouchableOpacity onPress={() => initiateCall('audio')} className="p-2 mr-1">
                    <Ionicons name="call" size={22} color="#f97316" />
                </TouchableOpacity>
                <TouchableOpacity onPress={() => initiateCall('video')} className="p-2 mr-1">
                    <Ionicons name="videocam" size={22} color="#f97316" />
                </TouchableOpacity>

                {/* 3-Dot Menu */}
                <TouchableOpacity onPress={() => setShowMenu(!showMenu)} className="p-2 bg-gray-50 rounded-full ml-auto">
                    <Ionicons name="ellipsis-vertical" size={22} color="#1f2937" />
                </TouchableOpacity>

                {/* Dropdown Menu */}
                {showMenu && (
                    <View className="absolute top-16 right-4 bg-white rounded-xl shadow-xl z-50 overflow-hidden w-48 border border-gray-100" style={{ elevation: 5 }}>
                        <TouchableOpacity
                            onPress={toggleBlockUser}
                            className="flex-row items-center px-4 py-3 active:bg-gray-50 bg-white"
                        >
                            <Ionicons
                                name={isBlocked ? "person-add-outline" : "person-remove-outline"}
                                size={20}
                                color={isBlocked ? "#16a34a" : "#dc2626"}
                            />
                            <Text className={`ml-3 font-medium ${isBlocked ? "text-green-600" : "text-red-600"}`}>
                                {isBlocked ? "Unblock User" : "Block User"}
                            </Text>
                        </TouchableOpacity>
                    </View>
                )}
            </View>

            <KeyboardAvoidingView
                style={{ flex: 1 }}
                behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                keyboardVerticalOffset={Platform.OS === 'ios' ? 10 : 0}
            >
                <FlatList
                    ref={flatListRef}
                    data={messages}
                    keyExtractor={(item, index) => item?.id?.toString() ?? index.toString()}
                    contentContainerStyle={{ paddingHorizontal: 16, paddingVertical: 20 }}
                    renderItem={({ item }) => {
                        let timeStr = "";
                        try {
                            if (item.created_at) {
                                const d = new Date(item.created_at.replace(' ', 'T'));
                                timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            }
                        } catch (e) { }

                        const isMe = item.is_mine;
                        const hasFile = !!item.file;
                        const fileUrl = item.file ? `${BASE_URL_ROOT}${item.file}` : null;
                        const isVideo = item.file_type === 'video';

                        return (
                            <TouchableOpacity
                                activeOpacity={0.9}
                                onLongPress={() => isMe && handleDeleteMessage(item.id)}
                                className={`mb-3 w-full flex-row ${isMe ? 'justify-end' : 'justify-start'}`}
                            >
                                <View
                                    className={`
                                        max-w-[75%] shadow-sm overflow-hidden
                                        ${isMe
                                            ? 'bg-orange-600 rounded-2xl rounded-tr-none'
                                            : 'bg-white rounded-2xl rounded-tl-none border border-gray-100'
                                        }
                                    `}
                                    style={{ elevation: 1 }}
                                >
                                    {hasFile && fileUrl && (
                                        <View className="mb-1">
                                            {isVideo ? (
                                                <Video
                                                    source={{ uri: fileUrl }}
                                                    style={{ width: 200, height: 150, backgroundColor: 'black' }}
                                                    useNativeControls
                                                    resizeMode={ResizeMode.CONTAIN}
                                                    isLooping
                                                />
                                            ) : (
                                                <TouchableOpacity onPress={() => {/* Maybe full screen view? */ }}>
                                                    <Image
                                                        source={{ uri: fileUrl }}
                                                        style={{ width: 200, height: 200 }}
                                                        resizeMode="cover"
                                                    />
                                                </TouchableOpacity>
                                            )}
                                        </View>
                                    )}

                                    {!!item.message && (
                                        <Text className={`text-base leading-5 px-4 pt-2 ${isMe ? 'text-white' : 'text-gray-800'} ${hasFile ? 'pb-1' : 'py-3'}`}>
                                            {item.message}
                                        </Text>
                                    )}

                                    <View className={`flex-row justify-end items-center px-4 pb-2 gap-1 ${!item.message ? 'pt-2' : ''}`}>
                                        <Text className={`text-[10px] font-medium ${isMe ? 'text-orange-100' : 'text-gray-400'}`}>
                                            {timeStr}
                                        </Text>
                                        {isMe && (
                                            <Ionicons
                                                name={item.seen == 1 ? "checkmark-done-outline" : "checkmark-outline"}
                                                size={14}
                                                color={item.seen == 1 ? "#dbeafe" : "rgba(255,255,255,0.7)"}
                                            />
                                        )}
                                    </View>
                                </View>
                            </TouchableOpacity>
                        );
                    }}
                    onContentSizeChange={() => flatListRef.current?.scrollToEnd({ animated: true })}
                    showsVerticalScrollIndicator={false}
                />

                <View className="px-4 pb-4 pt-2 bg-transparent">

                    {/* Selected Media Preview */}
                    {selectedMedia && (
                        <View className="bg-gray-100 p-2 rounded-xl mb-2 flex-row items-center justify-between border border-gray-200">
                            <View className="flex-row items-center gap-3">
                                {selectedMedia.type === 'video' ? (
                                    <View className="w-12 h-12 bg-black rounded-lg items-center justify-center">
                                        <Ionicons name="videocam" size={20} color="white" />
                                    </View>
                                ) : (
                                    <Image source={{ uri: selectedMedia.uri }} className="w-12 h-12 rounded-lg bg-gray-300" />
                                )}
                                <Text className="text-xs text-gray-500 max-w-[200px]" numberOfLines={1}>Selected Media</Text>
                            </View>
                            <TouchableOpacity onPress={cancelMedia} className="bg-gray-200 p-1 rounded-full">
                                <Ionicons name="close" size={16} color="#4b5563" />
                            </TouchableOpacity>
                        </View>
                    )}

                    {isBlocked ? (
                        <View className="bg-gray-100 p-4 rounded-xl items-center justify-center border border-gray-200">
                            <Text className="text-gray-500 font-medium text-center">
                                You have blocked this user. Unblock to send messages.
                            </Text>
                        </View>
                    ) : (
                        <View className="flex-row items-center bg-white rounded-full px-2 py-1.5 shadow-lg border border-gray-100" style={{ elevation: 4 }}>
                            <TouchableOpacity
                                onPress={() => {
                                    if (showEmojiPicker) {
                                        setShowEmojiPicker(false);
                                        // Optionally focus input here if desired
                                    } else {
                                        Keyboard.dismiss();
                                        setShowEmojiPicker(true);
                                    }
                                }}
                                className="p-2 bg-gray-50 rounded-full mr-1"
                            >
                                <Ionicons
                                    name={showEmojiPicker ? "keypad" : "happy"}
                                    size={24}
                                    color="#9ca3af"
                                />
                            </TouchableOpacity>

                            <TouchableOpacity onPress={pickMedia} className="p-2 bg-gray-50 rounded-full mr-2">
                                <Ionicons name="attach" size={24} color="#9ca3af" />
                            </TouchableOpacity>

                            <TextInput
                                className="flex-1 text-base text-gray-800 max-h-24 px-2 py-2"
                                placeholder={t('typeMessage')}
                                placeholderTextColor="#9ca3af"
                                value={text}
                                onChangeText={handleTyping}
                                onFocus={() => setShowEmojiPicker(false)}
                                multiline
                            />

                            <TouchableOpacity
                                onPress={sendMessage}
                                disabled={(!text.trim() && !selectedMedia) || uploading}
                                className={`p-3 rounded-full ml-2 shadow-sm ${(text.trim() || selectedMedia) && !uploading ? 'bg-orange-600 transform scale-100' : 'bg-gray-200 scale-95'}`}
                            >
                                {uploading ? <ActivityIndicator size="small" color="white" /> : <Ionicons name="send" size={20} color={(text.trim() || selectedMedia) ? "white" : "#9ca3af"} />}
                            </TouchableOpacity>
                        </View>
                    )}
                </View>

                {showEmojiPicker && (
                    <View style={{ height: 300, backgroundColor: 'white' }}>
                        <EmojiSelector
                            onEmojiSelected={(emoji) => setText(prev => prev + emoji)}
                            showSearchBar={false}
                            columns={8}
                        />
                    </View>
                )}
            </KeyboardAvoidingView>


        </View>
    );
};

export default ChatScreen;