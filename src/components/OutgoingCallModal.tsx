import React, { useState, useEffect } from 'react';
import { Modal, View, Text, TouchableOpacity, Image, StyleSheet, Alert } from 'react-native';
import { Audio } from 'expo-av';
import { Ionicons } from '@expo/vector-icons';
import CallWebView from './CallWebView';
import api, { WEBSITE_URL } from '../services/api';

interface OutgoingCallModalProps {
    visible: boolean;
    receiverName: string;
    receiverPhoto: string | null;
    type: 'audio' | 'video';
    callId: number | null;
    onEnd: () => void;
    myProfileId: string;
    receiverId: string;
}

const OutgoingCallModal: React.FC<OutgoingCallModalProps> = ({ visible, receiverName, receiverPhoto, type, callId: initialCallId, onEnd, myProfileId, receiverId }) => {
    const [status, setStatus] = useState<string>('ringing');
    const [seconds, setSeconds] = useState(0);
    const [sound, setSound] = useState<Audio.Sound | null>(null);
    const [callId, setCallId] = useState<number | null>(initialCallId);
    const [myPeerId, setMyPeerId] = useState<string | null>(null);
    const generatedPeerIdRef = React.useRef<string>(`caller_${myProfileId}_${Date.now()}`);

    // If visible changes to false, reset everything
    useEffect(() => {
        if (!visible) {
            setStatus('ringing');
            setSeconds(0);
            setCallId(null);
            setMyPeerId(null);
        } else {
            // Generate new ID when opening
            generatedPeerIdRef.current = `caller_${myProfileId}_${Date.now()}`;
        }
    }, [visible]);

    // Handle PeerOpen from CallWebView
    const handlePeerOpen = async (peerId: string) => {
        // Prevent duplicate initiations if we already have a callId or if we already set the peerId
        if (callId || myPeerId === peerId) return;

        setMyPeerId(peerId);

        // Now Initiate Call via API
        try {
            const formData = new FormData();
            formData.append('caller_id', myProfileId);
            formData.append('receiver_id', receiverId);
            formData.append('type', type);
            formData.append('peer_id', peerId);

            const res = await api.post('/initiate_call.php', formData);

            let newCallId: number | null = null;
            if (res.data?.status === true && res.data?.call_id) {
                // Handle JSON response: { status: true, call_id: 123 }
                newCallId = res.data.call_id;
            } else if (typeof res.data === 'string' && res.data.trim() !== 'error') {
                // Handle Plain Text response: "123"
                const parsed = parseInt(res.data, 10);
                if (!isNaN(parsed)) newCallId = parsed;
            } else if (typeof res.data === 'number') {
                newCallId = res.data;
            }

            if (newCallId) {
                setCallId(newCallId);
                playDialTone();
            } else {
                console.log("Call Init Failed", res.data);
                Alert.alert("Error", "Failed to connect call");
                onEnd();
            }
        } catch (e) {
            console.log("Init Call Error", e);
            onEnd();
        }
    };

    // Ref to track status for async operations
    const statusRef = React.useRef(status);

    useEffect(() => {
        statusRef.current = status;
    }, [status]);

    const soundRef = React.useRef<Audio.Sound | null>(null);

    // Play dial tone
    const playDialTone = async () => {
        try {
            if (soundRef.current) {
                await soundRef.current.unloadAsync();
            }
            const { sound } = await Audio.Sound.createAsync(
                { uri: 'https://assets.mixkit.co/active_storage/sfx/1359/1359-preview.mp3' },
                { shouldPlay: true, isLooping: true, volume: 0.5 }
            );

            // RACECONDITION CHECK: If status changed (e.g. accepted/ended) while loading, stop immediately
            if (statusRef.current !== 'ringing' || !visible) {
                await sound.stopAsync();
                await sound.unloadAsync();
                return;
            }

            soundRef.current = sound;
        } catch (e) { console.log('Dial tone error', e); }
    };

    const stopDialTone = async () => {
        try {
            if (soundRef.current) {
                await soundRef.current.stopAsync();
                await soundRef.current.unloadAsync();
                soundRef.current = null;
            }
        } catch (e) { console.log("Error stopping dial tone", e); }
    };

    // Timer Logic
    useEffect(() => {
        let timer: NodeJS.Timeout;
        if (status === 'accepted') {
            timer = setInterval(() => {
                setSeconds(s => s + 1);
            }, 1000);
        } else {
            setSeconds(0);
        }
        return () => clearInterval(timer);
    }, [status]);

    // Polling Status
    useEffect(() => {
        if (!visible || !callId) return;

        let pollInterval: NodeJS.Timeout;

        const checkStatus = async () => {
            try {
                const formData = new FormData();
                formData.append('profile_id', receiverId);
                formData.append('my_profile_id', myProfileId);

                const res = await api.post('/fetch_status.php', formData);

                if (res.data && res.data.call_update) {
                    const update = res.data.call_update;
                    // Ensure it's the relevant call
                    if (String(update.call_id) === String(callId)) {
                        const s = update.status;

                        if (s === 'accepted') {
                            if (status !== 'accepted') {
                                setStatus('accepted');
                                if (sound) {
                                    await sound.stopAsync();
                                    await sound.unloadAsync();
                                    setSound(null);
                                }
                            }
                        } else if (s === 'rejected' || s === 'ended') {
                            setStatus(s);
                            clearInterval(pollInterval);
                            if (sound) {
                                await sound.stopAsync();
                                await sound.unloadAsync();
                            }
                            setTimeout(onEnd, 1500);
                        }
                    }
                }
            } catch (e) {
                console.log("Poll Error", e);
            }
        };

        pollInterval = setInterval(checkStatus, 2000);

        return () => {
            clearInterval(pollInterval);
            if (sound) {
                sound.stopAsync();
                sound.unloadAsync();
            }
        };
    }, [visible, callId]);

    const formatTime = (secs: number) => {
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
    };

    if (!visible) return null;

    const photoUrl = receiverPhoto ? `${WEBSITE_URL}${receiverPhoto}` : null;

    return (
        <Modal visible={visible} animationType="slide" transparent={false}>
            <View style={styles.container}>
                {/* WebView for media - Always rendered to ensure Peer Init */}
                <View style={StyleSheet.absoluteFill}>
                    <CallWebView
                        myPeerId={generatedPeerIdRef.current}
                        shouldInitiateConnection={false}
                        type={type}
                        onStream={() => { }}
                        onClose={onEnd}
                        onError={(e) => console.log("WebRTC Error", e)}
                        onPeerOpen={handlePeerOpen}
                    />
                </View>

                {/* Cover WebView with UI if not accepted yet */}
                {status !== 'accepted' && (
                    <View style={[styles.background, { zIndex: 20 }]}>
                        {photoUrl && (
                            <Image source={{ uri: photoUrl }} style={styles.backgroundImage} blurRadius={10} />
                        )}
                        <View style={[styles.overlay, { backgroundColor: 'rgba(0,0,0,0.6)' }]} />
                    </View>
                )}

                <View style={[styles.content, { zIndex: 30 }]}>
                    <View style={styles.userInfo}>
                        <View style={styles.avatarContainer}>
                            {photoUrl ? (
                                <Image source={{ uri: photoUrl }} style={styles.avatar} />
                            ) : (
                                <View style={[styles.avatar, styles.placeholderAvatar]}>
                                    <Ionicons name="person" size={60} color="#fff" />
                                </View>
                            )}
                        </View>
                        <Text style={styles.callerName}>{receiverName}</Text>
                        <Text style={styles.callType}>
                            {status === 'accepted' ? formatTime(seconds) : (status === 'rejected' ? "Call Rejected" : (status === 'ended' ? "Call Ended" : "Calling..."))}
                        </Text>
                    </View>

                    <View style={styles.actions}>
                        <TouchableOpacity onPress={onEnd} style={styles.actionBtn}>
                            <View style={[styles.btnCircle, { backgroundColor: '#ef4444' }]}>
                                <Ionicons name="call" size={32} color="white" style={{ transform: [{ rotate: '135deg' }] }} />
                            </View>
                            <Text style={styles.btnText}>End Call</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </View>
        </Modal>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#1f2937' },
    background: { ...StyleSheet.absoluteFillObject },
    backgroundImage: { flex: 1, width: '100%', height: '100%', resizeMode: 'cover' },
    overlay: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(0,0,0,0.6)' },
    content: { flex: 1, justifyContent: 'space-between', paddingVertical: 80, paddingHorizontal: 20 },
    userInfo: { alignItems: 'center' },
    avatarContainer: { marginBottom: 20, elevation: 8 },
    avatar: { width: 120, height: 120, borderRadius: 60, borderWidth: 3, borderColor: 'white' },
    placeholderAvatar: { backgroundColor: '#6b7280', alignItems: 'center', justifyContent: 'center' },
    callerName: { fontSize: 28, fontWeight: 'bold', color: 'white', marginBottom: 8, textAlign: 'center' },
    callType: { fontSize: 18, color: '#d1d5db', letterSpacing: 1, fontWeight: '500' },
    actions: { alignItems: 'center', width: '100%', paddingBottom: 40 },
    actionBtn: { alignItems: 'center' },
    btnCircle: { width: 70, height: 70, borderRadius: 35, alignItems: 'center', justifyContent: 'center', marginBottom: 10, elevation: 5 },
    btnText: { color: 'white', fontSize: 14, fontWeight: '500' }
});

export default OutgoingCallModal;
