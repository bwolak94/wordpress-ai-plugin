declare global {
  interface Window {
    wpAiAgent: {
      nonce: string;
      root: string;
      adminUrl: string;
      version: string;
      userCaps: {
        edit_pages: boolean;
      };
    };
  }
}

export {};
