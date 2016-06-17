/**
 * GetDialogResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetDialogResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.Dialog dialog;

    private com.woodwing.enterprise.interfaces.services.wfl.Publication[] publications;

    private com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo publicationInfo;

    private com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData;

    private com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse getStatesResponse;

    private com.woodwing.enterprise.interfaces.services.wfl.Target[] targets;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[] relatedTargets;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo[] dossiers;

    public GetDialogResponse() {
    }

    public GetDialogResponse(
           com.woodwing.enterprise.interfaces.services.wfl.Dialog dialog,
           com.woodwing.enterprise.interfaces.services.wfl.Publication[] publications,
           com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo publicationInfo,
           com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData,
           com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse getStatesResponse,
           com.woodwing.enterprise.interfaces.services.wfl.Target[] targets,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[] relatedTargets,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo[] dossiers) {
           this.dialog = dialog;
           this.publications = publications;
           this.publicationInfo = publicationInfo;
           this.metaData = metaData;
           this.getStatesResponse = getStatesResponse;
           this.targets = targets;
           this.relatedTargets = relatedTargets;
           this.dossiers = dossiers;
    }


    /**
     * Gets the dialog value for this GetDialogResponse.
     * 
     * @return dialog
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Dialog getDialog() {
        return dialog;
    }


    /**
     * Sets the dialog value for this GetDialogResponse.
     * 
     * @param dialog
     */
    public void setDialog(com.woodwing.enterprise.interfaces.services.wfl.Dialog dialog) {
        this.dialog = dialog;
    }


    /**
     * Gets the publications value for this GetDialogResponse.
     * 
     * @return publications
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Publication[] getPublications() {
        return publications;
    }


    /**
     * Sets the publications value for this GetDialogResponse.
     * 
     * @param publications
     */
    public void setPublications(com.woodwing.enterprise.interfaces.services.wfl.Publication[] publications) {
        this.publications = publications;
    }


    /**
     * Gets the publicationInfo value for this GetDialogResponse.
     * 
     * @return publicationInfo
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo getPublicationInfo() {
        return publicationInfo;
    }


    /**
     * Sets the publicationInfo value for this GetDialogResponse.
     * 
     * @param publicationInfo
     */
    public void setPublicationInfo(com.woodwing.enterprise.interfaces.services.wfl.PublicationInfo publicationInfo) {
        this.publicationInfo = publicationInfo;
    }


    /**
     * Gets the metaData value for this GetDialogResponse.
     * 
     * @return metaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MetaData getMetaData() {
        return metaData;
    }


    /**
     * Sets the metaData value for this GetDialogResponse.
     * 
     * @param metaData
     */
    public void setMetaData(com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData) {
        this.metaData = metaData;
    }


    /**
     * Gets the getStatesResponse value for this GetDialogResponse.
     * 
     * @return getStatesResponse
     */
    public com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse getGetStatesResponse() {
        return getStatesResponse;
    }


    /**
     * Sets the getStatesResponse value for this GetDialogResponse.
     * 
     * @param getStatesResponse
     */
    public void setGetStatesResponse(com.woodwing.enterprise.interfaces.services.wfl.GetStatesResponse getStatesResponse) {
        this.getStatesResponse = getStatesResponse;
    }


    /**
     * Gets the targets value for this GetDialogResponse.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Target[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this GetDialogResponse.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
        this.targets = targets;
    }


    /**
     * Gets the relatedTargets value for this GetDialogResponse.
     * 
     * @return relatedTargets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[] getRelatedTargets() {
        return relatedTargets;
    }


    /**
     * Sets the relatedTargets value for this GetDialogResponse.
     * 
     * @param relatedTargets
     */
    public void setRelatedTargets(com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[] relatedTargets) {
        this.relatedTargets = relatedTargets;
    }


    /**
     * Gets the dossiers value for this GetDialogResponse.
     * 
     * @return dossiers
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo[] getDossiers() {
        return dossiers;
    }


    /**
     * Sets the dossiers value for this GetDialogResponse.
     * 
     * @param dossiers
     */
    public void setDossiers(com.woodwing.enterprise.interfaces.services.wfl.ObjectInfo[] dossiers) {
        this.dossiers = dossiers;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDialogResponse)) return false;
        GetDialogResponse other = (GetDialogResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.dialog==null && other.getDialog()==null) || 
             (this.dialog!=null &&
              this.dialog.equals(other.getDialog()))) &&
            ((this.publications==null && other.getPublications()==null) || 
             (this.publications!=null &&
              java.util.Arrays.equals(this.publications, other.getPublications()))) &&
            ((this.publicationInfo==null && other.getPublicationInfo()==null) || 
             (this.publicationInfo!=null &&
              this.publicationInfo.equals(other.getPublicationInfo()))) &&
            ((this.metaData==null && other.getMetaData()==null) || 
             (this.metaData!=null &&
              this.metaData.equals(other.getMetaData()))) &&
            ((this.getStatesResponse==null && other.getGetStatesResponse()==null) || 
             (this.getStatesResponse!=null &&
              this.getStatesResponse.equals(other.getGetStatesResponse()))) &&
            ((this.targets==null && other.getTargets()==null) || 
             (this.targets!=null &&
              java.util.Arrays.equals(this.targets, other.getTargets()))) &&
            ((this.relatedTargets==null && other.getRelatedTargets()==null) || 
             (this.relatedTargets!=null &&
              java.util.Arrays.equals(this.relatedTargets, other.getRelatedTargets()))) &&
            ((this.dossiers==null && other.getDossiers()==null) || 
             (this.dossiers!=null &&
              java.util.Arrays.equals(this.dossiers, other.getDossiers())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getDialog() != null) {
            _hashCode += getDialog().hashCode();
        }
        if (getPublications() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPublications());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPublications(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPublicationInfo() != null) {
            _hashCode += getPublicationInfo().hashCode();
        }
        if (getMetaData() != null) {
            _hashCode += getMetaData().hashCode();
        }
        if (getGetStatesResponse() != null) {
            _hashCode += getGetStatesResponse().hashCode();
        }
        if (getTargets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getTargets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getTargets(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRelatedTargets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRelatedTargets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRelatedTargets(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getDossiers() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDossiers());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDossiers(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(GetDialogResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialogResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dialog");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Dialog"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Dialog"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publications");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Publications"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Publication"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Publication"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publicationInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublicationInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PublicationInfo"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("metaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MetaData"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("getStatesResponse");
        elemField.setXmlName(new javax.xml.namespace.QName("", "GetStatesResponse"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "GetStatesResponse"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("targets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Targets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Target"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Target"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("relatedTargets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RelatedTargets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectTargetsInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectTargetsInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dossiers");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Dossiers"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectInfo"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
