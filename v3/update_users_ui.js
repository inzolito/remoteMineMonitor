const fs = require('fs');
const file = '/var/www/monitoreoLaboratorio/v3/frontend/src/pages/UsersPage.tsx';
let code = fs.readFileSync(file, 'utf8');

// 1. Initial form data
code = code.replace(
    "turno_7x7: null, turno_tipo: 'Día'",
    "turno_7x7: null, turno_tipo: 'Día', show_in_tickets: 0"
);
// replace multiple occurrences (edit modal reset)
code = code.replace(
    "turno_7x7: null, turno_tipo: 'Día'",
    "turno_7x7: null, turno_tipo: 'Día', show_in_tickets: 0"
);

// Edit User form mapping
code = code.replace(
    "turno_7x7: user.turno_7x7 !== undefined ? user.turno_7x7 : null,",
    "turno_7x7: user.turno_7x7 !== undefined ? user.turno_7x7 : null,\n                show_in_tickets: user.show_in_tickets ? 1 : 0,"
);

// Table Display - Add column header
code = code.replace(
    '<th className="pb-3 font-semibold text-muted-foreground">Estado</th>',
    '<th className="pb-3 font-semibold text-muted-foreground">Estado</th>\n                                    <th className="pb-3 font-semibold text-muted-foreground text-center">Módulo Tickets</th>'
);

// Table Display - Add column cell
const cellOld = `<td className="py-4">
                                            <span className={\`px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wide \${
                                                user.is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
                                            }\`}>
                                                {user.is_active ? 'Activo' : 'Inactivo'}
                                            </span>
                                        </td>`;
const cellNew = `<td className="py-4">
                                            <span className={\`px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wide \${
                                                user.is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-emerald-400'
                                            }\`}>
                                                {user.is_active ? 'Activo' : 'Inactivo'}
                                            </span>
                                        </td>
                                        <td className="py-4 text-center">
                                            {user.show_in_tickets ? (
                                                <span className="inline-flex items-center justify-center bg-blue-500/10 text-blue-600 dark:text-blue-400 px-2.5 py-1 rounded-full text-[10px] font-bold border border-blue-500/20">
                                                    Habilitado
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center justify-center bg-muted text-muted-foreground px-2.5 py-1 rounded-full text-[10px] font-medium border border-border">
                                                    Oculto
                                                </span>
                                            )}
                                        </td>`;
code = code.replace(cellOld, cellNew);

// Form Modal - Add Checkbox
const formFieldOld = `{/* Shift Configuration Section */}`;
const formFieldNew = `{/* Ticket Module Configuration */}
                            <div className="space-y-4 pt-4 border-t border-border/50">
                                <h4 className="text-sm font-semibold text-foreground flex items-center gap-2">
                                    <Ticket className="w-4 h-4 text-primary" />
                                    Módulo de Tickets
                                </h4>
                                <label className="flex items-center gap-3 p-3 border border-border/50 rounded-xl bg-muted/20 cursor-pointer hover:bg-muted/40 transition-colors">
                                    <div className="relative flex items-center">
                                        <input
                                            type="checkbox"
                                            className="sr-only peer"
                                            checked={formData.show_in_tickets === 1}
                                            onChange={(e) => setFormData({ ...formData, show_in_tickets: e.target.checked ? 1 : 0 })}
                                        />
                                        <div className="w-9 h-5 bg-muted peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                                    </div>
                                    <div className="flex flex-col">
                                        <span className="text-sm font-semibold text-foreground">Mostrar en Módulo Tickets</span>
                                        <span className="text-[11px] text-muted-foreground">Si se activa, los tickets de este usuario se contarán en las estadísticas generales.</span>
                                    </div>
                                </label>
                            </div>
                            
                            {/* Shift Configuration Section */}`;
code = code.replace(formFieldOld, formFieldNew);

// Add Ticket icon import if not exists
if (!code.includes('import {') || !code.includes('Ticket')) {
    code = code.replace('import { UserPlus, Pencil, Trash2, Shield, Settings2, Search, X, Check, Eye, EyeOff, Briefcase, Calendar, Info, Clock, Sun, Moon } from \'lucide-react\';',
                        'import { UserPlus, Pencil, Trash2, Shield, Settings2, Search, X, Check, Eye, EyeOff, Briefcase, Calendar, Info, Clock, Sun, Moon, Ticket } from \'lucide-react\';');
}

fs.writeFileSync(file, code);
console.log("Successfully updated UsersPage.tsx with show_in_tickets toggle.");
