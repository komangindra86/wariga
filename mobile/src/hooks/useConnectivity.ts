import NetInfo from '@react-native-community/netinfo';
import { useEffect, useState } from 'react';

export function useConnectivity() {
    const [isConnected, setIsConnected] = useState<boolean | null>(null);

    useEffect(() => {
        return NetInfo.addEventListener(state => {
            setIsConnected(state.isConnected);
        });
    }, []);

    return isConnected;
}
