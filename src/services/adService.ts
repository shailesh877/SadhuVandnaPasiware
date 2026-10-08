import { InterstitialAd, AdEventType, TestIds } from 'react-native-google-mobile-ads';

const INTERSTITIAL_AD_UNIT_ID = 'ca-app-pub-3506685647762869/1067662598';

class AdService {
    private interstitial: InterstitialAd | null = null;
    private isAdLoaded: boolean = false;

    constructor() {
        this.interstitial = InterstitialAd.createForAdRequest(INTERSTITIAL_AD_UNIT_ID, {
            requestNonPersonalizedAdsOnly: true,
        });

        this.interstitial.addAdEventListener(AdEventType.LOADED, () => {
            console.log('[AdService] Interstitial Loaded');
            this.isAdLoaded = true;
        });

        this.interstitial.addAdEventListener(AdEventType.CLOSED, () => {
            console.log('[AdService] Interstitial Closed');
            this.isAdLoaded = false;
            this.loadAd(); // Preload next one
        });

        this.interstitial.addAdEventListener(AdEventType.ERROR, (error) => {
            console.log('[AdService] Interstitial Error:', error?.message || error);
            this.isAdLoaded = false;
        });
    }

    public loadAd() {
        if (this.interstitial && !this.isAdLoaded) {
            console.log('[AdService] Loading Interstitial...');
            this.interstitial.load();
        }
    }

    public showAd() {
        if (this.interstitial && this.isAdLoaded) {
            console.log('[AdService] Showing Interstitial');
            this.interstitial.show();
        } else {
            console.log('[AdService] Ad not loaded, skipping show');
            this.loadAd(); // Try to load for next time
        }
    }

    public isLoaded(): boolean {
        return this.isAdLoaded;
    }
}

const adService = new AdService();
export default adService;
