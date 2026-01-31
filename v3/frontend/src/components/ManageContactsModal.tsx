import { X } from 'lucide-react';
import ContactsManager from './ContactsManager';

interface ManageContactsModalProps {
    isOpen: boolean;
    onClose: () => void;
    siteId: number;
}

const ManageContactsModal = ({ isOpen, onClose, siteId }: ManageContactsModalProps) => {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-in fade-in duration-200">
            <div className="bg-card w-full max-w-4xl rounded-xl shadow-2xl border border-border flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200">
                {/* Header */}
                <div className="flex items-center justify-between p-6 border-b border-border">
                    <h2 className="text-xl font-bold">Gestionar Personal</h2>
                    <button onClick={onClose} className="p-2 hover:bg-muted rounded-full transition-colors">
                        <X className="w-5 h-5 text-muted-foreground" />
                    </button>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6">
                    <ContactsManager siteId={siteId} />
                </div>
            </div>
        </div>
    );
};

export default ManageContactsModal;
