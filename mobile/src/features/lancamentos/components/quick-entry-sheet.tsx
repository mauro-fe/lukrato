import { Ionicons } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';

import { LancamentoQuickAction } from '@/src/features/lancamentos/types';
import { tokens } from '@/src/theme/tokens';

type QuickEntrySheetProps = {
  visible: boolean;
  actions: LancamentoQuickAction[];
  onClose: () => void;
};

export function QuickEntrySheet({
  visible,
  actions,
  onClose,
}: QuickEntrySheetProps) {
  const router = useRouter();

  function handleSelect(action: LancamentoQuickAction) {
    onClose();
    router.push(`/(app)/lancamentos/novo?mode=${action.id}`);
  }

  return (
    <Modal transparent visible={visible} animationType="slide" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Pressable style={styles.backdrop} onPress={onClose} />
        <View style={styles.sheet}>
          <View style={styles.handle} />
          <Text style={styles.title}>O que você quer registrar agora?</Text>
          <Text style={styles.description}>
            Antes do formulario, o app ajuda o usuario a escolher a intencao correta.
          </Text>

          <View style={styles.list}>
            {actions.map((action) => (
              <Pressable key={action.id} style={styles.row} onPress={() => handleSelect(action)}>
                <View style={styles.rowIcon}>
                  <Ionicons name={action.icon as never} size={20} color={tokens.colors.primaryStrong} />
                </View>
                <View style={styles.rowContent}>
                  <Text style={styles.rowTitle}>{action.label}</Text>
                  <Text style={styles.rowDescription}>{action.caption}</Text>
                </View>
                <Ionicons name="chevron-forward" size={18} color={tokens.colors.textMuted} />
              </Pressable>
            ))}
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  backdrop: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(9, 18, 28, 0.35)',
  },
  sheet: {
    backgroundColor: tokens.colors.surface,
    borderTopLeftRadius: tokens.radius.xl,
    borderTopRightRadius: tokens.radius.xl,
    paddingHorizontal: tokens.spacing.lg,
    paddingTop: tokens.spacing.sm,
    paddingBottom: tokens.spacing.xxl,
    gap: tokens.spacing.md,
  },
  handle: {
    alignSelf: 'center',
    width: 44,
    height: 5,
    borderRadius: tokens.radius.pill,
    backgroundColor: tokens.colors.border,
  },
  title: {
    color: tokens.colors.text,
    ...tokens.typography.heading,
  },
  description: {
    color: tokens.colors.textMuted,
    ...tokens.typography.body,
  },
  list: {
    gap: tokens.spacing.sm,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: tokens.spacing.sm,
    padding: tokens.spacing.md,
    borderRadius: tokens.radius.md,
    backgroundColor: tokens.colors.surfaceAlt,
    borderWidth: 1,
    borderColor: tokens.colors.border,
  },
  rowIcon: {
    width: 40,
    height: 40,
    borderRadius: tokens.radius.pill,
    backgroundColor: '#fff3e8',
    alignItems: 'center',
    justifyContent: 'center',
  },
  rowContent: {
    flex: 1,
    gap: 2,
  },
  rowTitle: {
    color: tokens.colors.text,
    ...tokens.typography.body,
  },
  rowDescription: {
    color: tokens.colors.textMuted,
    ...tokens.typography.small,
  },
});
