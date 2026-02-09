import React, { useState, useEffect } from 'react';
import { Modal, View, Text, TouchableOpacity, Image, StyleSheet, Vibration } from 'react-native';
import { Audio } from 'expo-av';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { Ionicons } from '@expo/vector-icons';
import CallWebView from './CallWebView';
import { WebView } from 'react-native-webview';
import api, { WEBSITE_URL } from '../services/api';

interface IncomingCallModalProps {
    visible: boolean;
    callerName: string;
    callerPhoto: string | null;
    type: 'audio' | 'video';
    onAccept: () => void;
    onReject: () => void;
    callId: number;
    callerPeerId?: string;
    callerProfileId?: string;
    myMemberId?: string | null;
}

const IncomingCallModal: React.FC<IncomingCallModalProps> = ({ visible, callerName, callerPhoto, type, onAccept, onReject, callId, callerPeerId, callerProfileId, myMemberId }) => {
    // Legacy component disabled favor of native Agora calling
    return null;
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#1f2937',
    },
    background: {
        ...StyleSheet.absoluteFillObject,
    },
    backgroundImage: {
        flex: 1,
        width: '100%',
        height: '100%',
        resizeMode: 'cover',
    },
    overlay: {
        ...StyleSheet.absoluteFillObject,
        backgroundColor: 'rgba(0,0,0,0.6)',
    },
    content: {
        flex: 1,
        justifyContent: 'space-between',
        paddingVertical: 80,
        paddingHorizontal: 20,
    },
    userInfo: {
        alignItems: 'center',
    },
    avatarContainer: {
        marginBottom: 20,
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.3,
        shadowRadius: 4.65,
        elevation: 8,
    },
    avatar: {
        width: 120,
        height: 120,
        borderRadius: 60,
        borderWidth: 3,
        borderColor: 'white',
    },
    placeholderAvatar: {
        backgroundColor: '#6b7280',
        alignItems: 'center',
        justifyContent: 'center',
    },
    callerName: {
        fontSize: 28,
        fontWeight: 'bold',
        color: 'white',
        marginBottom: 8,
        textAlign: 'center',
    },
    callType: {
        fontSize: 16,
        color: '#d1d5db',
        letterSpacing: 1,
    },
    actions: {
        flexDirection: 'row',
        justifyContent: 'space-around',
        width: '100%',
        paddingHorizontal: 20,
    },
    actionBtn: {
        alignItems: 'center',
    },
    btnCircle: {
        width: 70,
        height: 70,
        borderRadius: 35,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: 10,
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.25,
        shadowRadius: 3.84,
        elevation: 5,
    },
    btnText: {
        color: 'white',
        fontSize: 14,
        fontWeight: '500',
    }
});

export default IncomingCallModal;
