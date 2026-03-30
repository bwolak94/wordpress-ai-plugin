export interface AcfGroup {
  key: string;
  title: string;
  fields: AcfField[];
}

export interface AcfField {
  key: string;
  name: string;
  type: string;
  label: string;
}
