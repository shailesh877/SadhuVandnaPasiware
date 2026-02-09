import React, { useRef } from 'react';
import { StyleSheet, View, SafeAreaView, TouchableOpacity, Text, StatusBar } from 'react-native';
import { WebView } from 'react-native-webview';

interface WebCallModalProps {
    url: string;
    onClose: () => void;
}

const WebCallModal: React.FC<WebCallModalProps> = ({ url, onClose }) => {

    const handleMessage = (event: any) => {
        try {
            const data = event.nativeEvent.data;
            if (data === "CLOSE_MODAL") {
                onClose();
            }
        } catch (e) {
            console.log("WebView Message Error", e);
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <StatusBar barStyle="light-content" backgroundColor="black" />
            <View style={styles.header}>
                <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
                    <Text style={styles.closeText}>Close</Text>
                </TouchableOpacity>
            </View>
            <WebView
                source={{ uri: url }}
                javaScriptEnabled={true}
                domStorageEnabled={true}
                allowsInlineMediaPlayback={true}
                mediaPlaybackRequiresUserAction={false}
                onMessage={handleMessage}
                style={{ flex: 1, backgroundColor: 'black' }}
                // @ts-ignore
                onPermissionRequest={(req) => {
                    req.grant(req.resources);
                }}
                userAgent="Mozilla/5.0 (Linux; Android 10; Android SDK built for x86) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36"
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: 'black',
    },
    header: {
        height: 50,
        backgroundColor: 'black',
        justifyContent: 'center',
        alignItems: 'flex-end',
        paddingHorizontal: 20
    },
    closeBtn: {
        padding: 5,
    },
    closeText: {
        color: 'white',
        fontWeight: 'bold',
        fontSize: 16
    }
});

export default WebCallModal;
