import re

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'r') as f:
    content = f.read()

sidebar_block = """                            {/* Right Side (Analytics Sidebar) */}
                            {activeTab !== 'all' && (
                                <div className="lg:col-span-1 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">"""
new_sidebar_block = """                            {/* Right Side (Analytics Sidebar) */}
                                <div className="lg:col-span-1 p-3 bg-muted/5 flex flex-col gap-3.5 max-h-[550px] overflow-y-auto">"""
content = content.replace(sidebar_block, new_sidebar_block)

closing_block = """                                    </div>
                                </div>
                            )}
                        </div>
                    </div>"""
new_closing_block = """                                    </div>
                                </div>
                        </div>
                    </div>"""
content = content.replace(closing_block, new_closing_block)

with open('/var/www/monitoreoLaboratorio/v3/frontend/src/pages/ShiftsPage.tsx', 'w') as f:
    f.write(content)
