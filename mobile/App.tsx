import { AppProviders } from '@/components/AppProviders';
import { RootNavigator } from '@/navigation/RootNavigator';

export default function App() {
    return (
        <AppProviders>
            <RootNavigator />
        </AppProviders>
    );
}
