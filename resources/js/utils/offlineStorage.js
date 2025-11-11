/**
 * Offline Storage Utility for PWA
 * Handles localStorage with fallback and cleanup
 */

const STORAGE_PREFIX = 'coz_offline_';
const RESERVATION_KEY = `${STORAGE_PREFIX}reservation_data`;
const WIFI_KEY = `${STORAGE_PREFIX}wifi_credentials`;
const TIMER_KEY = `${STORAGE_PREFIX}timer_state`;

export const offlineStorage = {    /**
     * Save reservation data for offline access
     */
    saveReservation(reservation) {
        try {
            if (!reservation) return false;
            
            const data = {
                id: reservation.id,
                space_name: reservation.space_name || reservation.space_type?.name,
                space_type_id: reservation.space_type_id,
                start_time: reservation.start_time,
                end_time: reservation.end_time,
                status: reservation.status,
                total_price: reservation.total_price,
                payment_method: reservation.payment_method,
                hours: reservation.hours,
                pax: reservation.pax,
                customer_name: reservation.customer_name,
                customer_email: reservation.customer_email,
                customer_phone: reservation.customer_phone,
                customer_company_name: reservation.customer_company_name,
                created_at: reservation.created_at,
                updated_at: reservation.updated_at,
                saved_at: new Date().toISOString(),
                expires_at: reservation.end_time,
            };
            
            localStorage.setItem(RESERVATION_KEY, JSON.stringify(data));
            return true;
        } catch (error) {
            console.error('Failed to save reservation offline:', error);
            return false;
        }
    },

    /**
     * Get saved reservation data
     */
    getReservation() {
        try {
            const data = localStorage.getItem(RESERVATION_KEY);
            if (!data) return null;
            
            const reservation = JSON.parse(data);
            
            // Check if expired
            if (reservation.expires_at) {
                const expiresAt = new Date(reservation.expires_at);
                if (new Date() > expiresAt) {
                    this.clearReservation();
                    return null;
                }
            }
            
            return reservation;
        } catch (error) {
            console.error('Failed to get reservation offline:', error);
            return null;
        }
    },

    /**
     * Clear saved reservation
     */
    clearReservation() {
        try {
            localStorage.removeItem(RESERVATION_KEY);
            return true;
        } catch (error) {
            console.error('Failed to clear reservation:', error);
            return false;
        }
    },

    /**
     * Save WiFi credentials for offline access
     */
    saveWiFiCredentials(credentials) {
        try {
            if (!credentials) return false;
            
            const data = {
                ssid: credentials.ssid,
                username: credentials.username,
                password: credentials.password,
                expiresAt: credentials.expiresAt,
                saved_at: new Date().toISOString(),
            };
            
            localStorage.setItem(WIFI_KEY, JSON.stringify(data));
            return true;
        } catch (error) {
            console.error('Failed to save WiFi credentials:', error);
            return false;
        }
    },

    /**
     * Get saved WiFi credentials
     */
    getWiFiCredentials() {
        try {
            const data = localStorage.getItem(WIFI_KEY);
            if (!data) return null;
            
            const credentials = JSON.parse(data);
            
            // Check if expired
            if (credentials.expiresAt) {
                const expiresAt = new Date(credentials.expiresAt);
                if (new Date() > expiresAt) {
                    this.clearWiFiCredentials();
                    return null;
                }
            }
            
            return credentials;
        } catch (error) {
            console.error('Failed to get WiFi credentials:', error);
            return null;
        }
    },

    /**
     * Clear WiFi credentials
     */
    clearWiFiCredentials() {
        try {
            localStorage.removeItem(WIFI_KEY);
            return true;
        } catch (error) {
            console.error('Failed to clear WiFi credentials:', error);
            return false;
        }
    },

    /**
     * Save timer state for offline continuity
     */
    saveTimerState(state) {
        try {
            if (!state) return false;
            
            const data = {
                reservationId: state.reservationId,
                startTime: state.startTime,
                endTime: state.endTime,
                saved_at: new Date().toISOString(),
            };
            
            localStorage.setItem(TIMER_KEY, JSON.stringify(data));
            return true;
        } catch (error) {
            console.error('Failed to save timer state:', error);
            return false;
        }
    },

    /**
     * Get saved timer state
     */
    getTimerState() {
        try {
            const data = localStorage.getItem(TIMER_KEY);
            if (!data) return null;
            
            const state = JSON.parse(data);
            
            // Check if expired
            if (state.endTime) {
                const endTime = new Date(state.endTime);
                if (new Date() > endTime) {
                    this.clearTimerState();
                    return null;
                }
            }
            
            return state;
        } catch (error) {
            console.error('Failed to get timer state:', error);
            return null;
        }
    },

    /**
     * Clear timer state
     */
    clearTimerState() {
        try {
            localStorage.removeItem(TIMER_KEY);
            return true;
        } catch (error) {
            console.error('Failed to clear timer state:', error);
            return false;
        }
    },

    /**
     * Clean up all expired data
     */
    cleanupExpired() {
        try {
            this.getReservation(); // Will auto-clear if expired
            this.getWiFiCredentials(); // Will auto-clear if expired
            this.getTimerState(); // Will auto-clear if expired
            return true;
        } catch (error) {
            console.error('Failed to cleanup expired data:', error);
            return false;
        }
    },

    /**
     * Clear all offline data
     */
    clearAll() {
        try {
            this.clearReservation();
            this.clearWiFiCredentials();
            this.clearTimerState();
            return true;
        } catch (error) {
            console.error('Failed to clear all data:', error);
            return false;
        }
    },
};
