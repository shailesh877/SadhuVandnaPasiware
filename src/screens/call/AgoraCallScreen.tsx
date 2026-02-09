
import React, { useRef, useState, useEffect } from 'react';
import {
    View,
    Text,
    TouchableOpacity,
    StyleSheet,
    ActivityIndicator,
    PermissionsAndroid,
    Platform,
    Alert
} from 'react-native';
import {
    createAgoraRtcEngine,
    ChannelProfileType,
    ClientRoleType,
    IRtcEngine,
    RtcSurfaceView,
    RtcConnection,
    IRtcEngineEventHandler,
} from 'react-native-agora';
import { Ionicons } from '@expo/vector-icons';
import { NativeStackScreenProps } from '@react-navigation/native-stack';
import { RootStackParamList } from '../../navigation/RootNavigator';
import { getAgoraToken } from '../../services/AgoraService';
// You might need to add this to your types or just use 'any' for now if strictly typed
// import { RootStackParamList } from '../../navigation/types'; 
import { API_BASE_URL } from '../../services/api';

// Replace with your App ID from the server .env or a config file
// ideally fetched from API or constant
const AGORA_APP_ID = '42eb51e0bc30431cba75efefb9ea15ea'; // User needs to set this too or fetch from server? 
// Actually, usually App ID is public enough to be in client, but let's ask user to fill it or we can fetch it.
// For now, I will use a placeholder and ask user to replace it.

interface Props extends NativeStackScreenProps<RootStackParamList, 'AgoraCall'> { }

const AgoraCallScreen = ({ navigation, route }: any) => {
    const { channelId, isVideo, isCaller, otherUserId } = route.params;

    const agoraEngineRef = useRef<IRtcEngine | null>(null);
    const [isJoined, setIsJoined] = useState(false);
    const [remoteUid, setRemoteUid] = useState<number>(0);
    const [isMuted, setIsMuted] = useState(false);
    const [isSpeaker, setIsSpeaker] = useState(true);
    const [isCameraOn, setIsCameraOn] = useState(isVideo);
    const [token, setToken] = useState<string>('');

    // Permission request
    const getPermission = async () => {
        if (Platform.OS === 'android') {
            await PermissionsAndroid.requestMultiple([
                PermissionsAndroid.PERMISSIONS.RECORD_AUDIO,
                PermissionsAndroid.PERMISSIONS.CAMERA,
            ]);
        }
    };

    useEffect(() => {
        let isCancelled = false;

        const runSetup = async () => {
            try {
                if (isCancelled) return;
                console.log("Requesting permissions...");
                await getPermission();
                if (isCancelled) return;

                if (!channelId) {
                    Alert.alert("Error", "Invalid Call ID");
                    navigation.goBack();
                    return;
                }

                // 1. Get Token
                const myUid = isCaller
                    ? Number(otherUserId) + 100000   // caller uid
                    : Number(otherUserId);           // receiver uid

                console.log(`Fetching token for channel: ${channelId}, uid: ${myUid}`);

                const fetchedToken = await getAgoraToken(channelId, myUid);
                if (isCancelled) return;

                if (!fetchedToken) {
                    Alert.alert("Connection Error", "Could not fetch call token. Check server connection.");
                    navigation.goBack();
                    return;
                }
                setToken(fetchedToken);

                // 2. Init Engine
                if (isCancelled) return;
                console.log("Initializing Agora Engine...");
                agoraEngineRef.current = createAgoraRtcEngine();
                const agoraEngine = agoraEngineRef.current;

                if (!agoraEngine) return;

                agoraEngine.initialize({
                    appId: AGORA_APP_ID,
                    channelProfile: ChannelProfileType.ChannelProfileCommunication,
                });

                // 3. Enable Video/Audio
                // agoraEngine.enableVideo();
                // agoraEngine.enableAudio();
                agoraEngine.enableAudio();

                if (isVideo) {
                    agoraEngine.enableVideo();
                } else {
                    agoraEngine.disableVideo();
                }

                // agoraEngine.startPreview();
                agoraEngine.setEnableSpeakerphone(isVideo);


                // 4. Register Events
                agoraEngine.registerEventHandler({
                    onJoinChannelSuccess: (_connection: RtcConnection, uid: number) => {
                        console.log('Successfully joined channel: ' + channelId);
                        if (!isCancelled) setIsJoined(true);
                        // Explicitly mute/unmute to ensure state
                        agoraEngine.muteLocalAudioStream(false);
                        agoraEngine.muteLocalVideoStream(false);
                    },
                    onUserJoined: (_connection: RtcConnection, uid: number) => {
                        console.log('Remote user joined: ' + uid);
                        if (!isCancelled) setRemoteUid(uid);
                    },
                    onUserOffline: (_connection: RtcConnection, uid: number) => {
                        console.log('Remote user left: ' + uid);
                        if (!isCancelled) {
                            setRemoteUid(0);
                            Alert.alert("Call Ended", "User disconnected");
                            navigation.goBack();
                        }
                    },
                    onError: (err: number, msg: string) => {
                        console.error("Agora Error:", err, msg);
                    }
                });

                // 5. Join Channel
                if (isCancelled) return;
                console.log("Joining Channel...");
                const result = agoraEngine.joinChannel(fetchedToken, channelId, myUid, {
                    clientRoleType: ClientRoleType.ClientRoleBroadcaster,
                    channelProfile: ChannelProfileType.ChannelProfileCommunication,
                });
                console.log("Join Channel Result:", result);

                if (result !== 0) {
                    // -17 means already joined usually, which might happen if cleanup failed. 
                    // We can ignore it or try to leave and rejoin? 
                    // ideally we shouldn't hit this with isCancelled check.
                    if (result === -17) {
                        console.log("Already in channel, ignoring error.");
                        setIsJoined(true);
                    } else {
                        Alert.alert("Error", `Failed to join channel. Code: ${result}`);
                        navigation.goBack();
                    }
                }

                if (!isVideo) {
                    agoraEngine.disableVideo();
                    setIsCameraOn(false);
                }

            } catch (e: any) {
                if (!isCancelled) {
                    console.error("Setup Error:", e);
                    Alert.alert("Error", `Failed to setup call: ${e.message}`);
                    navigation.goBack();
                }
            }
        };

        runSetup();

        // Check call status periodically (handled by caller mostly, but good for both)
        const statusInterval = setInterval(async () => {
            try {

                const fd = new FormData();
                fd.append('channel_id', channelId);
                // Use the configured API URL
                const res = await fetch(`${API_BASE_URL}/check_call_status.php`, {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.status === 'success') {
                    if (data.call_status === 'rejected') {
                        Alert.alert("Call Rejected", "User busy or declined.");
                        leave();
                    } else if (data.call_status === 'ended') {
                        Alert.alert("Call Ended", "Call ended by user.");
                        leave();
                    }
                }
            } catch (e) { }
        }, 3000);

        return () => {
            clearInterval(statusInterval);
            isCancelled = true;
            // Cleanup
            if (agoraEngineRef.current) {
                agoraEngineRef.current.leaveChannel();
                agoraEngineRef.current.release();
                agoraEngineRef.current = null;
            }
        };
    }, []);

    // Remove the old setupVideoSDKEngine function definition entirely since it's now inside useEffect
    // or just leave it empty/unused. But better to replace the block.
    // For this tool usage, I am replacing the useEffect AND the setupVideoSDKEngine function.


    const leave = () => {
        agoraEngineRef.current?.leaveChannel();
        setRemoteUid(0);
        setIsJoined(false);
        navigation.goBack();
    };

    const toggleMute = () => {
        agoraEngineRef.current?.muteLocalAudioStream(!isMuted);
        setIsMuted(!isMuted);
    };

    const switchCamera = () => {
        agoraEngineRef.current?.switchCamera();
    };

    const toggleVideo = () => {
        if (isCameraOn) {
            agoraEngineRef.current?.disableVideo();
        } else {
            agoraEngineRef.current?.enableVideo();
        }
        setIsCameraOn(!isCameraOn);
    };

    if (!isJoined) {
        return (
            <View style={styles.container}>
                <ActivityIndicator size="large" color="#ea580c" />
                <Text style={styles.text}>Connecting...</Text>
            </View>
        );
    }

    return (
        <View style={styles.container}>
            {/* Remote Video (Full Screen) */}
            {remoteUid !== 0 ? (
                <RtcSurfaceView
                    style={styles.remoteVideo}
                    canvas={{ uid: remoteUid }}
                    connection={{ channelId }}
                    zOrderMediaOverlay={false}
                />
            ) : (
                <View style={styles.remoteVideoPlaceholder}>
                    <Text style={styles.text}>Waiting for user...</Text>
                    <ActivityIndicator size="small" color="white" style={{ marginTop: 10 }} />
                </View>
            )}

            {/* Local Video (PIP) */}
            {isCameraOn && (
                <View style={styles.localVideoContainer}>
                    <RtcSurfaceView
                        style={styles.localVideo}
                        canvas={{ uid: 0 }}
                        zOrderMediaOverlay={true}
                    />
                </View>
            )}

            {/* Controls */}
            <View style={styles.controls}>
                <TouchableOpacity onPress={toggleMute} style={[styles.btn, isMuted && styles.btnActive]}>
                    <Ionicons name={isMuted ? "mic-off" : "mic"} size={28} color="white" />
                </TouchableOpacity>

                <TouchableOpacity onPress={leave} style={[styles.btn, styles.btnEnd]}>
                    <Ionicons name="call" size={32} color="white" />
                </TouchableOpacity>

                <TouchableOpacity onPress={toggleVideo} style={[styles.btn, !isCameraOn && styles.btnActive]}>
                    <Ionicons name={isCameraOn ? "videocam" : "videocam-off"} size={28} color="white" />
                </TouchableOpacity>

                {isCameraOn && (
                    <TouchableOpacity onPress={switchCamera} style={styles.btn}>
                        <Ionicons name="camera-reverse" size={28} color="white" />
                    </TouchableOpacity>
                )}
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#1f2937', justifyContent: 'center', alignItems: 'center' },
    text: { color: 'white' },
    remoteVideo: { width: '100%', height: '100%' },
    remoteVideoPlaceholder: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    localVideoContainer: {
        position: 'absolute',
        top: 50,
        right: 20,
        width: 100,
        height: 150,
        borderRadius: 10,
        overflow: 'hidden',
        borderWidth: 2,
        borderColor: 'white',
        zIndex: 10
    },
    localVideo: { width: '100%', height: '100%' },
    controls: {
        position: 'absolute',
        bottom: 40,
        flexDirection: 'row',
        gap: 20,
        alignItems: 'center',
        zIndex: 20
    },
    btn: {
        backgroundColor: 'rgba(255,255,255,0.2)',
        padding: 15,
        borderRadius: 50,
        alignItems: 'center',
        justifyContent: 'center'
    },
    btnActive: {
        backgroundColor: 'white', // inverted for active state like muted
    },
    btnEnd: {
        backgroundColor: '#ef4444',
        padding: 18,
        transform: [{ scale: 1.1 }]
    }
});

export default AgoraCallScreen;
