import type { NativeStackScreenProps } from '@react-navigation/native-stack';
import { Controller, useForm, type Control, type FieldValues, type Path } from 'react-hook-form';
import { Alert, StyleSheet, type TextInputProps } from 'react-native';

import { endpoints } from '@/api/endpoints';
import { normalizeApiError } from '@/api/interceptors';
import { AppButton, AppCard, AppInput, AppText, ScreenContainer } from '@/components';
import { useAuthStore } from '@/store/authStore';
import { spacing } from '@/theme';
import type { RootStackParamList } from '@/types/navigation';

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function validateEmail(email: string) {
    const value = email.trim();
    return EMAIL_PATTERN.test(value)
        ? { value }
        : { error: 'Masukkan alamat email yang valid.' };
}

function messageFor(error: unknown) {
    return normalizeApiError(error).message;
}

function fieldError(error: unknown, field: string) {
    return normalizeApiError(error).fields?.[field]?.[0];
}

type LoginProps = NativeStackScreenProps<RootStackParamList, 'Login'>;
type LoginValues = { email: string; password: string };

export function LoginScreen({ navigation }: LoginProps) {
    const login = useAuthStore(state => state.login);
    const { control, handleSubmit, setError, formState } = useForm<LoginValues>({
        defaultValues: { email: '', password: '' },
    });
    const submit = handleSubmit(async values => {
        const email = validateEmail(values.email);
        if (email.error) {
            setError('email', { message: email.error });
            return;
        }
        if (!values.password) {
            setError('password', { message: 'Kata sandi wajib diisi.' });
            return;
        }
        try {
            await login({ email: email.value!, password: values.password });
            navigation.goBack();
        } catch (error) {
            setError('root', { message: messageFor(error) });
        }
    });

    return (
        <AuthLayout
            description="Masuk untuk menyimpan dan menyinkronkan hasil dewasa ayu. Kalender dan pencarian tetap tersedia tanpa akun."
            title="Selamat datang kembali"
        >
            <Controller control={control} name="email" render={({ field, fieldState }) => (
                <AppInput autoCapitalize="none" autoComplete="email" error={fieldState.error?.message} keyboardType="email-address" label="Email" onBlur={field.onBlur} onChangeText={field.onChange} value={field.value} />
            )} />
            <Controller control={control} name="password" render={({ field, fieldState }) => (
                <AppInput autoCapitalize="none" autoComplete="current-password" error={fieldState.error?.message} label="Kata sandi" onBlur={field.onBlur} onChangeText={field.onChange} secureTextEntry value={field.value} />
            )} />
            {formState.errors.root ? <AppText color="danger">{formState.errors.root.message}</AppText> : null}
            <AppButton fullWidth label="Masuk" loading={formState.isSubmitting} onPress={submit} />
            <AppButton fullWidth label="Lupa kata sandi?" onPress={() => navigation.navigate('ForgotPassword')} variant="ghost" />
            <AppButton fullWidth label="Belum punya akun? Daftar" onPress={() => navigation.replace('Register')} variant="outline" />
        </AuthLayout>
    );
}

type RegisterProps = NativeStackScreenProps<RootStackParamList, 'Register'>;
type RegisterValues = { name: string; email: string; password: string; password_confirmation: string };

export function RegisterScreen({ navigation }: RegisterProps) {
    const register = useAuthStore(state => state.register);
    const { control, handleSubmit, setError, formState } = useForm<RegisterValues>({
        defaultValues: { name: '', email: '', password: '', password_confirmation: '' },
    });
    const submit = handleSubmit(async values => {
        const email = validateEmail(values.email);
        if (values.name.trim().length < 2) {
            setError('name', { message: 'Nama minimal 2 karakter.' });
            return;
        }
        if (email.error) {
            setError('email', { message: email.error });
            return;
        }
        if (values.password.length < 8) {
            setError('password', { message: 'Kata sandi minimal 8 karakter.' });
            return;
        }
        if (values.password !== values.password_confirmation) {
            setError('password_confirmation', {
                message: 'Konfirmasi kata sandi tidak sama.',
            });
            return;
        }
        try {
            await register({
                ...values,
                name: values.name.trim(),
                email: email.value!,
            });
            navigation.popToTop();
        } catch (error) {
            (['name', 'email', 'password'] as const).forEach(field => {
                const message = fieldError(error, field);
                if (message) setError(field, { message });
            });
            setError('root', { message: messageFor(error) });
        }
    });

    return (
        <AuthLayout description="Buat akun agar hasil tersimpan aman dan dapat disinkronkan." title="Buat akun">
            <AuthField control={control} name="name" label="Nama" autoComplete="name" />
            <AuthField control={control} name="email" label="Email" autoCapitalize="none" autoComplete="email" keyboardType="email-address" />
            <AuthField control={control} name="password" label="Kata sandi" autoCapitalize="none" autoComplete="new-password" secureTextEntry />
            <AuthField control={control} name="password_confirmation" label="Ulangi kata sandi" autoCapitalize="none" autoComplete="new-password" secureTextEntry />
            {formState.errors.root ? <AppText color="danger">{formState.errors.root.message}</AppText> : null}
            <AppButton fullWidth label="Daftar" loading={formState.isSubmitting} onPress={submit} />
            <AppButton fullWidth label="Sudah punya akun? Masuk" onPress={() => navigation.replace('Login')} variant="ghost" />
        </AuthLayout>
    );
}

type ForgotProps = NativeStackScreenProps<RootStackParamList, 'ForgotPassword'>;
export function ForgotPasswordScreen({ navigation }: ForgotProps) {
    const { control, handleSubmit, setError, formState, getValues } = useForm<{ email: string }>({ defaultValues: { email: '' } });
    const submit = handleSubmit(async values => {
        const email = validateEmail(values.email);
        if (email.error) {
            setError('email', { message: email.error });
            return;
        }
        try {
            await endpoints.forgotPassword(email.value!);
            Alert.alert('Periksa email Anda', 'Jika email terdaftar, tautan reset telah dikirim.');
        } catch (error) {
            setError('root', { message: messageFor(error) });
        }
    });
    return (
        <AuthLayout description="Kami akan mengirim tautan pengaturan ulang jika email terdaftar." title="Lupa kata sandi">
            <AuthField control={control} name="email" label="Email" autoCapitalize="none" autoComplete="email" keyboardType="email-address" />
            {formState.errors.root ? <AppText color="danger">{formState.errors.root.message}</AppText> : null}
            <AppButton fullWidth label="Kirim tautan reset" loading={formState.isSubmitting} onPress={submit} />
            <AppButton fullWidth label="Masukkan token reset" onPress={() => navigation.navigate('ResetPassword', { email: getValues('email') })} variant="ghost" />
        </AuthLayout>
    );
}

type ResetProps = NativeStackScreenProps<RootStackParamList, 'ResetPassword'>;
type ResetValues = { token: string; email: string; password: string; password_confirmation: string };
export function ResetPasswordScreen({ navigation, route }: ResetProps) {
    const { control, handleSubmit, setError, formState } = useForm<ResetValues>({
        defaultValues: { token: route.params?.token ?? '', email: route.params?.email ?? '', password: '', password_confirmation: '' },
    });
    const submit = handleSubmit(async values => {
        const email = validateEmail(values.email);
        if (!values.token.trim()) {
            setError('token', { message: 'Token wajib diisi.' });
            return;
        }
        if (email.error) {
            setError('email', { message: email.error });
            return;
        }
        if (values.password.length < 8) {
            setError('password', { message: 'Kata sandi minimal 8 karakter.' });
            return;
        }
        if (values.password !== values.password_confirmation) {
            setError('password_confirmation', {
                message: 'Konfirmasi kata sandi tidak sama.',
            });
            return;
        }
        try {
            await endpoints.resetPassword({
                ...values,
                token: values.token.trim(),
                email: email.value!,
            });
            Alert.alert('Kata sandi diperbarui', 'Silakan masuk dengan kata sandi baru.');
            navigation.replace('Login');
        } catch (error) {
            setError('root', { message: messageFor(error) });
        }
    });
    return (
        <AuthLayout description="Masukkan token dari email dan buat kata sandi baru." title="Atur ulang kata sandi">
            <AuthField control={control} name="token" label="Token reset" autoCapitalize="none" />
            <AuthField control={control} name="email" label="Email" autoCapitalize="none" keyboardType="email-address" />
            <AuthField control={control} name="password" label="Kata sandi baru" secureTextEntry />
            <AuthField control={control} name="password_confirmation" label="Ulangi kata sandi baru" secureTextEntry />
            {formState.errors.root ? <AppText color="danger">{formState.errors.root.message}</AppText> : null}
            <AppButton fullWidth label="Simpan kata sandi" loading={formState.isSubmitting} onPress={submit} />
        </AuthLayout>
    );
}

function AuthLayout({ children, description, title }: React.PropsWithChildren<{ description: string; title: string }>) {
    return (
        <ScreenContainer style={styles.container}>
            <AppText variant="title" weight="bold">{title}</AppText>
            <AppText color="textMuted">{description}</AppText>
            <AppCard style={styles.card}>{children}</AppCard>
        </ScreenContainer>
    );
}

function AuthField<T extends FieldValues>({
    control,
    name,
    ...props
}: TextInputProps & { control: Control<T>; name: Path<T>; label: string }) {
    return <Controller control={control} name={name} render={({ field, fieldState }) => (
        <AppInput {...props} error={fieldState.error?.message} onBlur={field.onBlur} onChangeText={field.onChange} value={field.value} />
    )} />;
}

const styles = StyleSheet.create({
    card: { gap: spacing.md },
    container: { gap: spacing.md, justifyContent: 'center' },
});
