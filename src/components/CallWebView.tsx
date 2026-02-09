import React, { useRef } from 'react';
import { StyleSheet, View } from 'react-native';
import { WebView } from 'react-native-webview';

interface CallWebViewProps {
    myPeerId: string;
    targetPeerId?: string; // If 'shouldInitiateConnection' is true, this is required
    shouldInitiateConnection: boolean; // True = I will call the target. False = I wait for the call.
    type: 'audio' | 'video';
    onStream: (hasStream: boolean) => void;
    onClose: () => void;
    onError: (err: string) => void;
    onPeerOpen?: (id: string) => void;
}

const CallWebView: React.FC<CallWebViewProps> = ({ myPeerId, targetPeerId, shouldInitiateConnection, type, onStream, onClose, onError, onPeerOpen }) => {
    const webViewRef = useRef<WebView>(null);

    // Initial HTML content with PeerJS logic
    const htmlContent = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
        <style>
            body { margin: 0; background-color: #000; display: flex; justify-content: center; align-items: center; height: 100vh; color: white; font-family: sans-serif; }
            video { width: 100%; height: 100%; object-fit: cover; }
            #local-video { position: absolute; top: 20px; right: 20px; width: 100px; height: 150px; border: 2px solid white; z-index: 10; border-radius: 8px; object-fit: cover; }
            #remote-video { width: 100%; height: 100%; }
            .hidden { display: none; }
            #status { position: absolute; z-index: 5; font-size: 18px; }
        </style>
    </head>
    <body>
        <video id="remote-video" autoplay playsinline></video>
        <video id="local-video" autoplay playsinline muted></video>
        <div id="status">Initializing Media...</div>

        <script>
            const localVideo = document.getElementById('local-video');
            const remoteVideo = document.getElementById('remote-video');
            const statusDiv = document.getElementById('status');
            let peer;
            let localStream;
            let currentCall;

            const MY_PEER_ID = "${myPeerId}";
            const TARGET_PEER_ID = "${targetPeerId || ''}";
            const SHOULD_INITIATE = ${shouldInitiateConnection};
            const CALL_TYPE = "${type}";

            function log(msg) {
                window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'log', message: msg }));
            }

            // Init Peer
            peer = new Peer(MY_PEER_ID);

            peer.on('open', (id) => {
                log('Peer Open: ' + id);
                window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'peer-open', id: id }));
                statusDiv.innerText = "Waiting for connection...";
                
                // Get Stream
                const constraints = {
                    audio: true,
                    video: CALL_TYPE === 'video'
                };

                navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    localStream = stream;
                    localVideo.srcObject = stream;
                    if(CALL_TYPE === 'audio') {
                        localVideo.style.display = 'none'; // Hide local video for audio call
                    }
                    window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'stream-added' }));

                    // If we are the one who should connect (Receiver in this app's logic), we call the other person
                    if(SHOULD_INITIATE && TARGET_PEER_ID) {
                        statusDiv.innerText = "Connecting...";
                        const call = peer.call(TARGET_PEER_ID, stream);
                        setupCall(call);
                    }
                })
                .catch(err => {
                    log("GetUserMedia Error: " + err);
                    window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'error', message: 'Camera/Mic Permission Denied: ' + err.name }));
                });
            });

            peer.on('call', (call) => {
                log("Receiving Call...");
                // Note: In our current logic, we answer immediately if we have stream.
                if(localStream) {
                    call.answer(localStream);
                    setupCall(call);
                } else {
                    // Wait for stream...
                    // simple retry
                    setTimeout(() => {
                        if(localStream) {
                            call.answer(localStream);
                            setupCall(call);
                        }
                    }, 1000);
                }
            });

            peer.on('error', (err) => {
                log("Peer Error: " + err);
                window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'error', message: err.type }));
            });

            function setupCall(call) {
                currentCall = call;
                call.on('stream', (remoteStream) => {
                    log("Remote Stream Received");
                    statusDiv.classList.add('hidden');
                    remoteVideo.srcObject = remoteStream;
                });
                call.on('close', () => {
                    log("Call Closed");
                    window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'close' }));
                });
                 call.on('error', (e) => {
                    log("Call Error: " + e);
                 });
            }
        </script>
    </body>
    </html>
    `;

    const handleMessage = (event: any) => {
        try {
            const data = JSON.parse(event.nativeEvent.data);
            if (data.type === 'log') console.log('[WebView]', data.message);
            if (data.type === 'peer-open') {
                if (onPeerOpen) onPeerOpen(data.id);
            }
            if (data.type === 'stream-added') onStream(true);
            if (data.type === 'close') onClose();
            if (data.type === 'error') onError(data.message);
        } catch (e) {
            console.log("WebView Message Parse Error", e);
        }
    };

    return (
        <View style={styles.container}>
            <WebView
                ref={webViewRef}
                originWhitelist={['*']}
                source={{ html: htmlContent }}
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
            />
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: 'black',
    },
});

export default CallWebView;
