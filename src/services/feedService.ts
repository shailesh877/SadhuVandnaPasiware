import api from './api';

export const feedService = {
    getPosts: async (userId: string | number) => {
        try {
            const response = await api.get(`/get_posts.php?user_id=${userId}`);
            if (response.data && response.data.status === 'success') {
                return response.data.data;
            }
            return [];
        } catch (error) {
            console.error('Fetch posts error:', error);
            throw error;
        }
    },

    likePost: async (postId: string | number, userId: string | number) => {
        try {
            const formData = new FormData();
            formData.append('action', 'like');
            formData.append('id', String(postId));
            formData.append('user_id', String(userId));

            const response = await api.post('/get_posts.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            return response.data;
        } catch (error) {
            console.error('Like post error:', error);
            throw error;
        }
    },

    createPost: async (formData: FormData) => {
        try {
            const response = await api.post('/create_post.php', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            return response.data;
        } catch (error) {
            console.error('Create post error:', error);
            throw error;
        }
    }
};
