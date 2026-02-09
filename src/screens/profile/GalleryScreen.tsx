import React, { useEffect, useState } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, Dimensions, ActivityIndicator, Modal } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import api, { API_BASE_URL } from '../../services/api';
import ImageViewer from 'react-native-image-zoom-viewer';

const { width } = Dimensions.get('window');
const GALLERY_URL = `${API_BASE_URL.replace('/Api', '')}/uploads/gallery/`;

const GalleryScreen = ({ navigation }: any) => {
    const [images, setImages] = useState<any[]>([]); // Formatted for ImageViewer {url: ''}
    const [loading, setLoading] = useState(true);
    const [visible, setVisible] = useState(false);
    const [currentImageIndex, setCurrentImageIndex] = useState(0);

    useEffect(() => {
        fetchGallery();
    }, []);

    const fetchGallery = async () => {
        try {
            const res = await api.get('/get_gallery.php');
            if (res.data.status === 'success') {
                // Map to ImageViewer format: { url: '...' }
                const formattedImages = res.data.data
                    .map((item: any) => {
                        const imgFile = item.image || item.photo;
                        if (!imgFile) return null;

                        const url = imgFile.startsWith('http')
                            ? imgFile
                            : `${GALLERY_URL}${imgFile}`;
                        return { url };
                    })
                    .filter((item: any) => item !== null);

                setImages(formattedImages);
            }
        } catch (error) {
            console.error("Gallery fetch error", error);
        } finally {
            setLoading(false);
        }
    };

    const openImage = (index: number) => {
        setCurrentImageIndex(index);
        setVisible(true);
    };

    const renderItem = ({ item, index }: { item: any, index: number }) => (
        <TouchableOpacity
            className="m-0.5 relative bg-gray-100"
            style={{ width: width / 3 - 4, height: width / 3 - 4 }}
            onPress={() => openImage(index)}
        >
            <Image
                source={{ uri: item.url }}
                className="w-full h-full"
                resizeMode="cover"
            />
        </TouchableOpacity>
    );

    return (
        <SafeAreaView className="flex-1 bg-white" edges={['top']}>
            <View className="flex-row items-center p-4 border-b border-gray-100">
                <TouchableOpacity onPress={() => navigation.goBack()} className="mr-3">
                    <Ionicons name="arrow-back" size={24} color="black" />
                </TouchableOpacity>
                <Text className="text-xl font-bold text-gray-800">Gallery</Text>
            </View>

            {loading ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#ea580c" />
                </View>
            ) : (
                <FlatList
                    data={images}
                    renderItem={renderItem}
                    keyExtractor={(item, index) => index.toString()}
                    numColumns={3}
                    contentContainerStyle={{ padding: 2 }}
                    ListEmptyComponent={
                        <View className="items-center py-20">
                            <Ionicons name="images-outline" size={48} color="#ccc" />
                            <Text className="text-gray-500 mt-4">No photos available</Text>
                        </View>
                    }
                />
            )}

            <Modal visible={visible} transparent={true} onRequestClose={() => setVisible(false)}>
                <ImageViewer
                    imageUrls={images}
                    index={currentImageIndex}
                    onSwipeDown={() => setVisible(false)}
                    enableSwipeDown={true}
                    renderHeader={() => (
                        <TouchableOpacity
                            onPress={() => setVisible(false)}
                            style={{ position: 'absolute', top: 40, right: 20, zIndex: 9999 }}
                        >
                            <Ionicons name="close-circle" size={32} color="white" />
                        </TouchableOpacity>
                    )}
                />
            </Modal>
        </SafeAreaView>
    );
};

export default GalleryScreen;
