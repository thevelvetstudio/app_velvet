import { Head, Link, useForm } from '@inertiajs/react';
import { motion, useReducedMotion } from 'framer-motion';
import { useState } from 'react';
import {
    FiAlertCircle,
    FiArrowRight,
    FiCheck,
    FiEye,
    FiEyeOff,
    FiLock,
    FiMail,
    FiShield,
} from 'react-icons/fi';
import GuestLayout from '@/Layouts/GuestLayout';

const reveal = (delay, reducedMotion) => reducedMotion
    ? undefined
    : {
        initial: { opacity: 0, y: 8 },
        animate: { opacity: 1, y: 0 },
        transition: { duration: 0.3, delay, ease: 'easeOut' },
    };

function FieldError({ message }) {
    if (!message) return null;

    return (
        <p className="velvet-login__error" role="alert">
            <FiAlertCircle aria-hidden="true" size={14} />
            {message}
        </p>
    );
}

export default function Login({ status, canResetPassword }) {
    const reducedMotion = useReducedMotion();
    const [showPassword, setShowPassword] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    const fieldAnimation = (delay, hasError) => ({
        ...reveal(delay, reducedMotion),
        animate: hasError && !reducedMotion
            ? { opacity: 1, y: 0, x: [0, -3, 3, -2, 2, 0] }
            : { opacity: 1, y: 0, x: 0 },
        transition: hasError && !reducedMotion
            ? { duration: 0.25 }
            : { duration: 0.3, delay, ease: 'easeOut' },
    });

    return (
        <GuestLayout variant="login">
            <Head title="Acceso interno" />

            <div className="velvet-login__content">
                <motion.div {...reveal(0, reducedMotion)}>
                    <p className="velvet-login__eyebrow">Espacio interno</p>
                    <h1 className="velvet-login__title">
                        Bienvenido
                        <br />
                        de nuevo.
                    </h1>
                    <p className="velvet-login__description">
                        Ingresa para continuar gestionando
                        <br className="hidden sm:block" />
                        el talento Velvet.
                    </p>
                </motion.div>

                {status && (
                    <div className="velvet-login__status" role="status">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="velvet-login__form">
                    <motion.div {...fieldAnimation(0.12, Boolean(errors.email))}>
                        <label htmlFor="email" className="velvet-login__label">Email</label>
                        <div className={`velvet-login__field ${errors.email ? 'is-error' : ''}`}>
                            <FiMail className="velvet-login__field-icon" aria-hidden="true" size={22} />
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                autoComplete="username"
                                autoFocus
                                placeholder="tu@email.com"
                                onChange={(event) => setData('email', event.target.value)}
                            />
                        </div>
                        <FieldError message={errors.email} />
                    </motion.div>

                    <motion.div {...fieldAnimation(0.18, Boolean(errors.password))} className="mt-5">
                        <label htmlFor="password" className="velvet-login__label">Contraseña</label>
                        <div className={`velvet-login__field ${errors.password ? 'is-error' : ''}`}>
                            <FiLock className="velvet-login__field-icon" aria-hidden="true" size={22} />
                            <input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                value={data.password}
                                autoComplete="current-password"
                                placeholder="Tu contraseña"
                                onChange={(event) => setData('password', event.target.value)}
                            />
                            <button
                                type="button"
                                className="velvet-login__password-toggle"
                                onClick={() => setShowPassword((visible) => !visible)}
                                aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                                title={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                            >
                                {showPassword ? <FiEyeOff aria-hidden="true" size={21} /> : <FiEye aria-hidden="true" size={21} />}
                            </button>
                        </div>
                        <FieldError message={errors.password} />
                    </motion.div>

                    <motion.div {...reveal(0.24, reducedMotion)} className="velvet-login__options">
                        <label className="velvet-login__remember">
                            <input
                                type="checkbox"
                                name="remember"
                                checked={data.remember}
                                onChange={(event) => setData('remember', event.target.checked)}
                            />
                            <span className="velvet-login__checkbox" aria-hidden="true">
                                {data.remember && <FiCheck size={14} strokeWidth={3} />}
                            </span>
                            <span>Recordarme</span>
                        </label>

                        {canResetPassword && (
                            <Link href={route('password.request')} className="velvet-login__forgot">
                                ¿Olvidaste tu contraseña?
                            </Link>
                        )}
                    </motion.div>

                    <motion.button
                        {...reveal(0.3, reducedMotion)}
                        type="submit"
                        disabled={processing}
                        className="velvet-login__submit"
                    >
                        <span>{processing ? 'Verificando acceso...' : 'Iniciar sesión'}</span>
                        {!processing && <FiArrowRight className="velvet-login__submit-arrow" aria-hidden="true" size={22} />}
                    </motion.button>
                </form>

                <motion.div {...reveal(0.36, reducedMotion)} className="velvet-login__secure-divider">
                    <span />
                    <div><FiLock aria-hidden="true" size={18} /><span>Acceso seguro al espacio interno</span></div>
                    <span />
                </motion.div>

                <motion.div {...reveal(0.42, reducedMotion)} className="velvet-login__security-note">
                    <FiShield aria-hidden="true" size={30} />
                    <span />
                    <div>
                        <strong>Tu trabajo, nuestra prioridad.</strong>
                        <small>Información segura. Talento real.</small>
                    </div>
                </motion.div>

                <motion.div {...reveal(0.48, reducedMotion)}>
                    <Link href="/apply" className="velvet-login__apply-link">
                        <span><strong>¿Eres nuevo?</strong> Aplica y crece con nosotros.</span>
                        <FiArrowRight aria-hidden="true" size={18} />
                    </Link>
                </motion.div>
            </div>
        </GuestLayout>
    );
}
