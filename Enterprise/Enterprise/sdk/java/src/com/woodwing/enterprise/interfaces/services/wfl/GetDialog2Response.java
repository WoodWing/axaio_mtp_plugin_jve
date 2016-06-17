/**
 * GetDialog2Response.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class GetDialog2Response  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.Dialog dialog;

    private com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[] pubChannels;

    private com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData;

    private com.woodwing.enterprise.interfaces.services.wfl.Target[] targets;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[] relatedTargets;

    private com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations;

    public GetDialog2Response() {
    }

    public GetDialog2Response(
           com.woodwing.enterprise.interfaces.services.wfl.Dialog dialog,
           com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[] pubChannels,
           com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData,
           com.woodwing.enterprise.interfaces.services.wfl.Target[] targets,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[] relatedTargets,
           com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations) {
           this.dialog = dialog;
           this.pubChannels = pubChannels;
           this.metaData = metaData;
           this.targets = targets;
           this.relatedTargets = relatedTargets;
           this.relations = relations;
    }


    /**
     * Gets the dialog value for this GetDialog2Response.
     * 
     * @return dialog
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Dialog getDialog() {
        return dialog;
    }


    /**
     * Sets the dialog value for this GetDialog2Response.
     * 
     * @param dialog
     */
    public void setDialog(com.woodwing.enterprise.interfaces.services.wfl.Dialog dialog) {
        this.dialog = dialog;
    }


    /**
     * Gets the pubChannels value for this GetDialog2Response.
     * 
     * @return pubChannels
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[] getPubChannels() {
        return pubChannels;
    }


    /**
     * Sets the pubChannels value for this GetDialog2Response.
     * 
     * @param pubChannels
     */
    public void setPubChannels(com.woodwing.enterprise.interfaces.services.wfl.PubChannelInfo[] pubChannels) {
        this.pubChannels = pubChannels;
    }


    /**
     * Gets the metaData value for this GetDialog2Response.
     * 
     * @return metaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.MetaData getMetaData() {
        return metaData;
    }


    /**
     * Sets the metaData value for this GetDialog2Response.
     * 
     * @param metaData
     */
    public void setMetaData(com.woodwing.enterprise.interfaces.services.wfl.MetaData metaData) {
        this.metaData = metaData;
    }


    /**
     * Gets the targets value for this GetDialog2Response.
     * 
     * @return targets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Target[] getTargets() {
        return targets;
    }


    /**
     * Sets the targets value for this GetDialog2Response.
     * 
     * @param targets
     */
    public void setTargets(com.woodwing.enterprise.interfaces.services.wfl.Target[] targets) {
        this.targets = targets;
    }


    /**
     * Gets the relatedTargets value for this GetDialog2Response.
     * 
     * @return relatedTargets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[] getRelatedTargets() {
        return relatedTargets;
    }


    /**
     * Sets the relatedTargets value for this GetDialog2Response.
     * 
     * @param relatedTargets
     */
    public void setRelatedTargets(com.woodwing.enterprise.interfaces.services.wfl.ObjectTargetsInfo[] relatedTargets) {
        this.relatedTargets = relatedTargets;
    }


    /**
     * Gets the relations value for this GetDialog2Response.
     * 
     * @return relations
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Relation[] getRelations() {
        return relations;
    }


    /**
     * Sets the relations value for this GetDialog2Response.
     * 
     * @param relations
     */
    public void setRelations(com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations) {
        this.relations = relations;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof GetDialog2Response)) return false;
        GetDialog2Response other = (GetDialog2Response) obj;
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
            ((this.pubChannels==null && other.getPubChannels()==null) || 
             (this.pubChannels!=null &&
              java.util.Arrays.equals(this.pubChannels, other.getPubChannels()))) &&
            ((this.metaData==null && other.getMetaData()==null) || 
             (this.metaData!=null &&
              this.metaData.equals(other.getMetaData()))) &&
            ((this.targets==null && other.getTargets()==null) || 
             (this.targets!=null &&
              java.util.Arrays.equals(this.targets, other.getTargets()))) &&
            ((this.relatedTargets==null && other.getRelatedTargets()==null) || 
             (this.relatedTargets!=null &&
              java.util.Arrays.equals(this.relatedTargets, other.getRelatedTargets()))) &&
            ((this.relations==null && other.getRelations()==null) || 
             (this.relations!=null &&
              java.util.Arrays.equals(this.relations, other.getRelations())));
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
        if (getPubChannels() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPubChannels());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPubChannels(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMetaData() != null) {
            _hashCode += getMetaData().hashCode();
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
        if (getRelations() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRelations());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRelations(), i);
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
        new org.apache.axis.description.TypeDesc(GetDialog2Response.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">GetDialog2Response"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dialog");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Dialog"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Dialog"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pubChannels");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PubChannels"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PubChannelInfo"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PubChannelInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("metaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MetaData"));
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
        elemField.setFieldName("relations");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Relations"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Relation"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Relation"));
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
