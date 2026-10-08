import React, { useEffect, useState, useRef } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Alert, Platform } from 'react-native';
import { navigationRef, navigateWithRetry } from '../navigation/navigationRef';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Audio } from 'expo-av';
import api, { WEBSITE_URL } from '../services/api';

const GlobalCallListener = () => {
    const [incomingCall, setIncomingCall] = useState<any>(null);
    const [myUserId, setMyUserId] = useState<string | null>(null);
    const soundRef = useRef<Audio.Sound | null>(null);
    const isProcessing = useRef(false);

    useEffect(() => {
        // Load user ID
        AsyncStorage.getItem('user').then(u => {
            if (u) setMyUserId(JSON.parse(u).id);
        });
    }, []);

    useEffect(() => {
        if (!myUserId) return;

        const checkStatus = async () => {
            if (isProcessing.current) return;
            
            // If already in a call, don't show another popup
            if (navigationRef.isReady()) {
                const currentRoute = navigationRef.getCurrentRoute()?.name;
                if (currentRoute === 'AgoraCall') {
                    if (incomingCall) setIncomingCall(null);
                    return;
                }
            }
            try {
                const payload = { user_id: myUserId };
                const res = await api.post('get_global_status.php', payload);

                if (res.data.status && res.data.incoming_call) {
                    const call = res.data.incoming_call;
                    call.my_profile_id_ref = res.data.my_profile_id;

                    setIncomingCall((prev: any) => {
                        if (prev && prev.call_id === call.call_id) return prev;
                        return call;
                    });
                } else {
                    if (!isProcessing.current) {
                        setIncomingCall(null);
                    }
                }
            } catch (e) { }
        };

        const interval = setInterval(checkStatus, 5000); // Check every 5 seconds
        return () => clearInterval(interval);
    }, [myUserId]);

    // Handle Ringtone
    useEffect(() => {
        const currentRoute = navigationRef.isReady() ? navigationRef.getCurrentRoute()?.name : null;
        if (incomingCall && !isProcessing.current && currentRoute !== 'AgoraCall') {
            playRingtone();
        } else {
            stopRingtone();
        }
        
        return () => {
            stopRingtone();
        };
    }, [incomingCall]);

    const playRingtone = async () => {
        if (soundRef.current) return;
        try {
            const { sound } = await Audio.Sound.createAsync(
                { uri: 'https://assets.mixkit.co/active_storage/sfx/1359/1359-preview.mp3' },
                { shouldPlay: true, isLooping: true }
            );
            soundRef.current = sound;
        } catch (e) { console.log("Sound Error", e); }
    };

    const stopRingtone = async () => {
        try {
            if (soundRef.current) {
                await soundRef.current.stopAsync();
                await soundRef.current.unloadAsync();
                soundRef.current = null;
            }
        } catch (e) { }
    };

    const handleAccept = async () => {
        if (!incomingCall || isProcessing.current) return;
        isProcessing.current = true;
        
        const currentCall = incomingCall;
        setIncomingCall(null);
        await stopRingtone();

        // Notify server
        try {
            const payload = {
                call_id: currentCall.call_id,
                status: 'accepted'
            };
            await api.post('update_call_status.php', payload);
        } catch (e) { }

        let channelId = currentCall.peer_id;

        navigateWithRetry('AgoraCall', {
            channelId: channelId, 
            isVideo: (currentCall.type === 'video'),
            isCaller: false,
            otherUserId: currentCall.caller_id,
            myProfileIdFallback: currentCall.my_profile_id_ref
        });

        setTimeout(() => {
            isProcessing.current = false;
        }, 3000);
    };

    const handleReject = async () => {
        if (!incomingCall || isProcessing.current) return;
        isProcessing.current = true;

        const currentCall = incomingCall;
        setIncomingCall(null);
        await stopRingtone();

        try {
            const payload = {
                call_id: currentCall.call_id,
                status: 'rejected'
            };
            await api.post('update_call_status.php', payload);
        } catch (e) { }

        setTimeout(() => {
            isProcessing.current = false;
        }, 3000);
    };

    const currentRoute = navigationRef.isReady() ? navigationRef.getCurrentRoute()?.name : null;
    if (!incomingCall || currentRoute === 'AgoraCall') return null;

    return (
        <View style={styles.container}>
            <View style={styles.card}>
                <Text style={styles.title}>Incoming {incomingCall.type} Call</Text>
                <Text style={styles.name}>{incomingCall.caller_name}</Text>

                <View style={styles.row}>
                    <TouchableOpacity onPress={handleReject} style={[styles.btn, styles.reject]}>
                        <Text style={styles.btnText}>Decline</Text>
                    </TouchableOpacity>
                    <TouchableOpacity onPress={handleAccept} style={[styles.btn, styles.accept]}>
                        <Text style={styles.btnText}>Answer</Text>
                    </TouchableOpacity>
                </View>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        position: 'absolute', top: 0, left: 0, right: 0, bottom: 0,
        backgroundColor: 'rgba(0,0,0,0.7)',
        justifyContent: 'center', alignItems: 'center', zIndex: 9999
    },
    card: {
        width: '80%', backgroundColor: 'white', padding: 20, borderRadius: 15, alignItems: 'center',
        elevation: 5
    },
    title: { fontSize: 16, color: '#666', marginBottom: 10 },
    name: { fontSize: 22, fontWeight: 'bold', marginBottom: 30 },
    row: { flexDirection: 'row', gap: 20 },
    btn: { paddingVertical: 12, paddingHorizontal: 30, borderRadius: 30 },
    reject: { backgroundColor: '#ef4444' },
    accept: { backgroundColor: '#22c55e' },
    btnText: { color: 'white', fontWeight: 'bold', fontSize: 16 }
});

export default GlobalCallListener;
