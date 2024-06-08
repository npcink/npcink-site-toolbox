export interface PublicShareData {
  sharePosition: string;
  shareTop: string;
  pageData: PageData;
}

interface PageData {
  title: string;
  description: string;
  image: string;
  url: string;
  type: string;
}
