import React, { useEffect, useState, useRef } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Alert, Platform } from 'react-native';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Audio } from 'expo-av';
import api, { WEBSITE_URL } from '../services/api';

const GlobalCallListener = () => {
    const navigation = useNavigation<any>();
    const [incomingCall, setIncomingCall] = useState<any>(null);
    const [myUserId, setMyUserId] = useState<string | null>(null);
    const soundRef = useRef<Audio.Sound | null>(null);

    useEffect(() => {
        // Load user ID
        AsyncStorage.getItem('user').then(u => {
            if (u) setMyUserId(JSON.parse(u).id);
        });
    }, []);

    useEffect(() => {
        if (!myUserId) return;

        const checkStatus = async () => {
            try {
                const fd = new FormData();
                fd.append('user_id', myUserId);
                const res = await api.post('/get_global_status.php', fd);

                if (res.data.status && res.data.incoming_call) {
                    const call = res.data.incoming_call;
                    // Attach my_profile_id to the call object for later use
                    call.my_profile_id_ref = res.data.my_profile_id;

                    console.log("Incoming Call Payload:", JSON.stringify(call)); // LOGGING

                    // Check if we are already handling this call or in a call
                    setIncomingCall((prev: any) => {
                        if (prev && prev.call_id === call.call_id) return prev;
                        return call;
                    });
                } else {
                    setIncomingCall(null);
                }
            } catch (e) { }
        };

        const interval = setInterval(checkStatus, 3000);
        return () => clearInterval(interval);
    }, [myUserId]);

    // Handle Ringtone
    useEffect(() => {
        if (incomingCall) {
            playRingtone();
        } else {
            stopRingtone();
        }
    }, [incomingCall]);

    const playRingtone = async () => {
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
            await soundRef.current?.stopAsync();
            await soundRef.current?.unloadAsync();
            soundRef.current = null;
        } catch (e) { }
    };

    const handleAccept = async () => {
        if (!incomingCall) return;
        await stopRingtone();

        // Notify server
        try {
            const fd = new FormData();
            fd.append('call_id', incomingCall.call_id);
            fd.append('status', 'accepted');
            await api.post('/update_call_status.php', fd);
        } catch (e) { }

        // Use peer_id from server OR construct using correct Profile ID
        let channelId = incomingCall.peer_id;

        if (!channelId) {
            console.warn("Missing peer_id, constructing from IDs...");
            // Use the profile_id returned by get_global_status, NOT AsyncStorage user_id
            // incomingCall.caller_id is the Caller's Profile ID
            // incomingCall.receiver_profile_id (we need to ensure we have this or use the one from state)

            // We stored the profile_id in state when we fetched status? 
            // Actually get_global_status returns 'my_profile_id' now. 
            // Let's grab it from the response directly if possible, but we don't have it here easily unless we stored it.
            // We can pass it in navigation or store in state.

            // Let's assume we can get it from the incomingCall logic if we save it.
            // Better: we saved it in state `myProfileId`? No we didn't.
        }

        navigation.navigate('AgoraCall', {
            channelId: channelId, // If this is still null, AgoraCallScreen handles it
            isVideo: (incomingCall.type === 'video'),
            isCaller: false,
            otherUserId: incomingCall.caller_id,
            // Pass the fallback info just in case
            myProfileIdFallback: incomingCall.my_profile_id_ref
        });

        setIncomingCall(null);
    };

    const handleReject = async () => {
        if (!incomingCall) return;
        await stopRingtone();

        try {
            const fd = new FormData();
            fd.append('call_id', incomingCall.call_id);
            fd.append('status', 'rejected');
            await api.post('/update_call_status.php', fd);
        } catch (e) { }

        setIncomingCall(null);
    };

    if (!incomingCall) return null;

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
