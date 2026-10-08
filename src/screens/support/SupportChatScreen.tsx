import React, { useState, useRef, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, FlatList, ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api from '../../services/api';

interface Message {
    id: string;
    role: 'user' | 'model';
    text: string;
    timestamp: Date;
}

const SUGGESTIONS = [
    { title: "समाचार कैसे पोस्ट करें? 📰", text: "समाचार कैसे पोस्ट करें?" },
    { title: "प्रोफाइल कैसे बनाएं? 👤", text: "मैट्रिमोनी प्रोफाइल कैसे बनाएं?" },
    { title: "जॉब्स कैसे सर्च करें? 💼", text: "जॉब्स कैसे देखें?" },
    { title: "स्मार्ट कार्ड क्या है? 🆔", text: "स्मार्ट कार्ड क्या है और कैसे बनाएं?" },
    { title: "सपोर्ट से संपर्क करें 📞", text: "सपोर्ट टीम का कांटेक्ट नंबर क्या है?" }
];

const SupportChatScreen = ({ navigation }: any) => {
    const [messages, setMessages] = useState<Message[]>([
        {
            id: 'welcome',
            role: 'model',
            text: "नमस्ते! मैं आपका AI असिस्टेंट Linko हूँ। मैं Linkup ऐप का उपयोग करने में आपकी सहायता कर सकता हूँ। आप मुझसे मैट्रिमोनी, न्यूज़, जॉब्स या किसी भी समस्या के बारे में पूछ सकते हैं।",
            timestamp: new Date()
        }
    ]);
    const [inputText, setInputText] = useState('');
    const [loading, setLoading] = useState(false);
    const flatListRef = useRef<FlatList>(null);

    // Auto scroll to bottom
    const scrollToBottom = () => {
        setTimeout(() => {
            flatListRef.current?.scrollToEnd({ animated: true });
        }, 100);
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, loading]);

    const handleSendMessage = async (textToSend: string) => {
        const trimmedText = textToSend.trim();
        if (!trimmedText) return;

        setInputText('');
        
        // Add user message
        const userMsg: Message = {
            id: Date.now().toString(),
            role: 'user',
            text: trimmedText,
            timestamp: new Date()
        };
        setMessages(prev => [...prev, userMsg]);
        setLoading(true);

        try {
            // Format history for Gemini API
            const historyPayload = messages.map(msg => ({
                role: msg.role,
                text: msg.text
            }));

            const res = await api.post('ai_support.php', {
                prompt: trimmedText,
                history: historyPayload
            });

            if (res.data && res.data.status === 'success') {
                const aiMsg: Message = {
                    id: (Date.now() + 1).toString(),
                    role: 'model',
                    text: res.data.reply,
                    timestamp: new Date()
                };
                setMessages(prev => [...prev, aiMsg]);
            } else {
                throw new Error(res.data?.message || "Failed to get reply");
            }
        } catch (error) {
            console.error("AI Support Error:", error);
            const errMsg: Message = {
                id: (Date.now() + 1).toString(),
                role: 'model',
                text: "क्षमा करें, मुझे रिस्पॉन्स प्राप्त करने में समस्या हो रही है। कृपया सुनिश्चित करें कि आपका इंटरनेट कनेक्शन ठीक है और पुनः प्रयास करें।",
                timestamp: new Date()
            };
            setMessages(prev => [...prev, errMsg]);
        } finally {
            setLoading(false);
        }
    };

    const renderMessageItem = ({ item }: { item: Message }) => {
        const isUser = item.role === 'user';
        return (
            <View className={`flex-row mb-4 ${isUser ? 'justify-end' : 'justify-start'}`}>
                {!isUser && (
                    <View className="w-8 h-8 rounded-full bg-orange-100 items-center justify-center mr-2 self-end shadow-sm">
                        <Text className="text-orange-600 text-xs font-bold">L</Text>
                    </View>
                )}
                <View 
                    className={`max-w-[78%] p-3.5 rounded-2xl shadow-sm ${
                        isUser 
                            ? 'bg-orange-500 rounded-tr-none' 
                            : 'bg-white border border-gray-100 rounded-tl-none'
                    }`}
                >
                    <Text className={`text-[14px] leading-5 font-medium ${isUser ? 'text-white' : 'text-gray-800'}`}>
                        {item.text}
                    </Text>
                    <Text className={`text-[9px] mt-1 self-end ${isUser ? 'text-orange-100' : 'text-gray-400'}`}>
                        {item.timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </Text>
                </View>
            </View>
        );
    };

    return (
        <SafeAreaView className="flex-1 bg-gray-50" edges={['top']}>
            {/* Header */}
            <View className="flex-row items-center px-4 py-3 bg-white border-b border-gray-200 shadow-sm z-10">
                <TouchableOpacity onPress={() => navigation.goBack()} className="p-1 mr-2">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <View className="w-10 h-10 rounded-full bg-orange-100 items-center justify-center mr-3 shadow-sm">
                    <Text className="text-orange-600 text-base font-extrabold">LK</Text>
                </View>
                <View className="flex-1">
                    <Text className="text-base font-bold text-gray-900">Linko</Text>
                    <View className="flex-row items-center">
                        <View className="w-2 h-2 rounded-full bg-green-500 mr-1.5" />
                        <Text className="text-[10px] text-gray-500 font-semibold">AI Support Assistant</Text>
                    </View>
                </View>
            </View>

            {/* Messages Screen Container */}
            <KeyboardAvoidingView 
                behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                className="flex-1"
                keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 0}
            >
                <FlatList
                    ref={flatListRef}
                    data={messages}
                    renderItem={renderMessageItem}
                    keyExtractor={item => item.id}
                    contentContainerStyle={{ padding: 16, paddingBottom: 10 }}
                    ListFooterComponent={loading ? (
                        <View className="flex-row mb-4 justify-start items-center">
                            <View className="w-8 h-8 rounded-full bg-orange-100 items-center justify-center mr-2 self-end shadow-sm">
                                <Text className="text-orange-600 text-xs font-bold">L</Text>
                            </View>
                            <View className="bg-white border border-gray-100 rounded-2xl rounded-tl-none p-3.5 shadow-sm">
                                <View className="flex-row items-center px-1.5 py-0.5">
                                    <ActivityIndicator size="small" color="#ea580c" />
                                    <Text className="text-gray-400 text-xs ml-2 font-medium">Linko is typing...</Text>
                                </View>
                            </View>
                        </View>
                    ) : null}
                />

                {/* Quick suggestions */}
                {messages.length === 1 && (
                    <View className="py-2 bg-transparent">
                        <ScrollView 
                            horizontal 
                            showsHorizontalScrollIndicator={false} 
                            contentContainerStyle={{ paddingHorizontal: 16 }}
                        >
                            {SUGGESTIONS.map((item, index) => (
                                <TouchableOpacity
                                    key={index}
                                    onPress={() => handleSendMessage(item.text)}
                                    className="bg-white rounded-full border border-orange-100 px-4 py-2 mr-2.5 shadow-sm flex-row items-center"
                                    activeOpacity={0.8}
                                >
                                    <Text className="text-xs font-bold text-orange-600">{item.title}</Text>
                                </TouchableOpacity>
                            ))}
                        </ScrollView>
                    </View>
                )}

                {/* Input Area */}
                <View className="p-3 bg-white border-t border-gray-200 flex-row items-center">
                    <TextInput
                        className="flex-1 bg-gray-100 border border-gray-200 rounded-full px-4 py-2.5 mr-2 text-sm text-gray-800"
                        placeholder="अपनी समस्या यहाँ लिखें..."
                        placeholderTextColor="#9ca3af"
                        value={inputText}
                        onChangeText={setInputText}
                        onSubmitEditing={() => handleSendMessage(inputText)}
                        multiline={false}
                    />
                    <TouchableOpacity 
                        onPress={() => handleSendMessage(inputText)}
                        disabled={!inputText.trim()}
                        className={`w-10 h-10 rounded-full items-center justify-center shadow-sm ${
                            inputText.trim() ? 'bg-orange-500' : 'bg-gray-200'
                        }`}
                        activeOpacity={0.8}
                    >
                        <Ionicons name="send" size={16} color="white" />
                    </TouchableOpacity>
                </View>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    dot: {
        opacity: 0.6,
    }
});

export default SupportChatScreen;
